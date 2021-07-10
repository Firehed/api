<?php

declare(strict_types=1);

namespace Firehed\API;

use BadMethodCallException;
use DomainException;
use Firehed\API\Errors\HandlerInterface;
use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Firehed\Common\ClassMapper;
use Firehed\Input\Containers\ParsedInput;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use OutOfBoundsException;
use UnexpectedValueException;

use function strtoupper;
use function preg_match;
use function array_key_exists;

class Dispatcher implements RequestHandlerInterface
{
    const ENDPOINT_LIST = '__endpoint_list__.php';
    const PARSER_LIST = '__parser_list__.php';

    /** @var ?ContainerInterface */
    private $container;

    /** @var bool */
    private $containerHasAuthProviders = false;

    /** @var bool */
    private $containerHasErrorHandler = false;

    /** @var string | string[][] */
    private $endpointList = self::ENDPOINT_LIST;

    /** @var string | string[] */
    private $parserList = self::PARSER_LIST;

    /** @var MiddlewareInterface[] */
    private $psrMiddleware = [];

    /**
     * Provide PSR-15 middleware to run. This is treated as a queue (FIFO), and
     * starts before routing and other internal processes.
     *
     * @param MiddlewareInterface $mw
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $mw): self
    {
        $this->psrMiddleware[] = $mw;
        return $this;
    }

    /**
     * Provide a DI Container/Service Locator class or array. During
     * dispatching, this structure will be queried for the routed endpoint by
     * the fully-qualified class name. If the container has a class at that
     * key, it will be used during execution; if not, the default behavior is
     * to automatically instanciate it.
     *
     * @param ContainerInterface $container Container
     * @return self
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        // Auto-detect auth components
        if ($container->has(Authentication\ProviderInterface::class)
            && $container->has(Authorization\ProviderInterface::class)
        ) {
            $this->containerHasAuthProviders = true;
        }

        // Auto-detect error handler
        if ($container->has(HandlerInterface::class)) {
            $this->containerHasErrorHandler = true;
        }

        return $this;
    }

    /**
     * Set the parser list. Can be an array consumable by ClassMapper or
     * a string representing a file parsable by same. The list must map
     * MIME-types to Firehed\Input\ParserInterface class names.
     *
     * @internal Overrides the standard parser list. Used primarily for unit
     * testing.
     * @param string | string[] $parserList The parser list or its path
     * @return self
     */
    public function setParserList($parserList): self
    {
        $this->parserList = $parserList;
        return $this;
    }

    /**
     * Set the endpoint list. Can be an array consumable by ClassMapper or
     * a string representing a file parsable by same. The list must be
     * filterable by HTTP method and map absolute URI path components to
     * controller methods.
     *
     * @internal Overrides the standard endpoint list. Used primarily for unit
     * testing.
     * @param string | string[][] $endpointList The endpoint list or its path
     * @return self
     */
    public function setEndpointList($endpointList): self
    {
        $this->endpointList = $endpointList;
        return $this;
    }

    /**
     * PSR-15 Entrypoint
     *
     * This method is intended for internal use only, and should not be called
     * outside of the context of a Middleware's RequestHandler parameter
     * @internal
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->doDispatch($request);
    }

    /**
     * Execute the request
     *
     * @param ServerRequestInterface $request The request to process
     * @throws \TypeError if both execute and handleException have bad return
     * types
     * @throws \LogicException if the dispatcher is misconfigured
     * @throws \RuntimeException on 404-type errors
     * @return ResponseInterface the completed HTTP response
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        // Delegate to PSR-15 middleware when possible
        $mwDispatcher = new MiddlewareDispatcher($this, $this->psrMiddleware);
        return $mwDispatcher->handle($request);
    }

    /**
     * @return array{
     *   ?class-string<Interfaces\EndpointInterface>,
     *   ?array<string, string>,
     * }
     */
    private function routeRequest(ServerRequestInterface $request): array
    {
        $endpoints = self::loadConfigFile($this->endpointList);
        $method = strtoupper($request->getMethod());
        if (!array_key_exists($method, $endpoints)) {
            return [null, null];
        }
        $endpointsForMethod = $endpoints[$method];
        $requestPath = $request->getUri()->getPath();
        foreach ($endpointsForMethod as $uri => $fqcn) {
            $pattern = '#^' . $uri . '#';
            if (preg_match($pattern, $requestPath, $match)) {
                // TODO: filter numeric keys
                return [$fqcn, $match];
            }
        }
        // nope
        // print_r($endpointsForMethod);
        return [null, null];
    }

    private function doDispatch(ServerRequestInterface $request): ResponseInterface
    {
        /** @var ?Interfaces\EndpointInterface */
        $endpoint = null;
        try {
            // var_dump($this->endpointList);
            [$fqcn, $uriData] = $this->routeRequest($request);
            if (!$fqcn) {
                throw new OutOfBoundsException('Endpoint not found', 404);
            }
            assert($uriData !== null);
            if ($this->container && $this->container->has($fqcn)) {
                $endpoint = $this->container->get($fqcn);
            } else {
                $endpoint = new $fqcn;
            }
            if ($this->containerHasAuthProviders
                && $endpoint instanceof Interfaces\AuthenticatedEndpointInterface
            ) {
                assert($this->container !== null); // hasAuthProviders guarantees this
                $auth = $this->container
                    ->get(Authentication\ProviderInterface::class)
                    ->authenticate($request);
                $endpoint->setAuthentication($auth);
                $this->container
                    ->get(Authorization\ProviderInterface::class)
                    ->authorize($endpoint, $auth);
            }
            $safe_input = $this->parseInput($request)
                ->addData(new ParsedInput($uriData))
                ->addData($this->getQueryStringData($request))
                ->validate($endpoint);

            $response = $endpoint->execute($safe_input);
        } catch (Throwable $e) {
            try {
                if ($endpoint instanceof HandlesOwnErrorsInterface) {
                    $response = $endpoint->handleException($e);
                } else {
                    throw $e;
                }
            } catch (Throwable $e) {
                // If an application-wide handler has been defined, use the
                // response that it generates. If not, just rethrow the
                // exception for the system default (if defined) to handle.
                if ($this->containerHasErrorHandler) {
                    assert($this->container !== null); // hasAuthProviders guarantees this
                    $response = $this->container
                        ->get(HandlerInterface::class)
                        ->handle($request, $e);
                } else {
                    throw $e;
                }
            }
        }
        return $response;
    }

    /**
     * Parse the raw input body based on the content type
     *
     * @param ServerRequestInterface $request
     * @return ParsedInput the parsed input data
     */
    private function parseInput(ServerRequestInterface $request): ParsedInput
    {
        $data = [];
        // Presence of Content-type header indicates PUT/POST; parse it. We
        // don't use $_POST because additional content types are supported.
        // Since PSR-7 doesn't specify parsing the body of most MIME-types,
        // we'll hand off to our own set of parsers.
        $header = $request->getHeader('Content-type');
        if ($header) {
            $directives = explode(';', $header[0]);
            $mediaType = array_shift($directives);
            // Future: trim and format directives; e.g. ' charset=utf-8' =>
            // ['charset' => 'utf-8']
            // list($parser_class) = (new ClassMapper($this->parserList))
            //     ->search($mediaType);
            // FIXME: string or array
            $parsers = self::loadConfigFile($this->parserList);
            if (!array_key_exists($mediaType, $parsers)) {
                throw new OutOfBoundsException('Unsupported Content-type', 415);
            }
            $parserClass = $parsers[$mediaType];
            $parser = new $parserClass;
            $data = $parser->parse((string)$request->getBody());
        }
        return new ParsedInput($data);
    }

    private function getQueryStringData(ServerRequestInterface $request): ParsedInput
    {
        $uri = $request->getUri();
        $query = $uri->getQuery();
        $data = [];
        parse_str($query, $data);
        return new ParsedInput($data);
    }

    /**
     * @param string|string[] $file
     * @return string[]
     */
    private static function loadConfigFile($file): array
    {
        if (is_array($file)) {
            return $file;
        } elseif (is_string($file)) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException('Invalid file');
                // throw
            }
            // if php include
            // elseif json
            // Avoid weird scope issues
            return (fn () => include $file)();
        } else {
            throw new InvalidArgumentException('Invalid format');
        }
    }
}

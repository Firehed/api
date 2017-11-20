<?php

declare(strict_types=1);

namespace Firehed\API;

use BadMethodCallException;
use DomainException;
use Firehed\Common\ClassMapper;
use Firehed\Input\Containers\ParsedInput;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use OutOfBoundsException;
use UnexpectedValueException;

class Dispatcher
{

    private $container = [];
    private $endpoint_list;
    private $error_handler;
    private $parser_list;
    private $response_middleware = [];
    private $request;
    private $uri_data;

    /**
     * Add a callback to run on the response after controller executation (or
     * error handling) has finished.
     *
     * This must be a callable with the following signature:
     *
     * function(ResponseInterface $response, callable $next): ResponseInterface
     *
     * The callback may modify the response, and either pass it off to the next
     * handler (by using `return $next($response)`) or return it immediately,
     * bypassing all future callbacks.
     *
     * The callbacks are treated as a queue (FIFO)
     *
     * @param callable the callback to execute
     * @return self
     */
    public function addResponseMiddleware(callable $callback): self {
        $this->response_middleware[] = $callback;
        return $this;
    }

    /**
     * Provide a DI Container/Service Locator class or array. During
     * dispatching, this structure will be queried for the routed endpoint by
     * the fully-qualified class name. If the container has a class at that
     * key, it will be used during execution; if not, the default behavior is
     * to automatically instanciate it.
     *
     * @param ContainerInterface Container
     * @return self
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Inject the request
     *
     * @param RequestInterface The request
     * @return self
     */
    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the parser list. Can be an array consumable by ClassMapper or
     * a string representing a file parsable by same. The list must map
     * MIME-types to Firehed\Input\ParserInterface class names.
     *
     * @param array|string The parser list or its path
     * @return self
     */
    public function setParserList($parser_list): self
    {
        $this->parser_list = $parser_list;
        return $this;
    }

    /**
     * Set the endpoint list. Can be an array consumable by ClassMapper or
     * a string representing a file parsable by same. The list must be
     * filterable by HTTP method and map absolute URI path components to
     * controller methods.
     *
     * @param array|string The endpoint list or its path
     * @return self
     */
    public function setEndpointList($endpoint_list): self
    {
        $this->endpoint_list = $endpoint_list;
        return $this;
    }

    /**
     * Execute the request
     *
     * @throws TypeError if both execute and handleException have bad return
     * types
     * @throws LogicException if the dispatcher is misconfigured
     * @throws RuntimeException on 404-type errors
     * @return ResponseInterface the completed HTTP response
     */
    public function dispatch(): ResponseInterface
    {
        if (null === $this->request ||
            null === $this->parser_list ||
            null === $this->endpoint_list) {
            throw new BadMethodCallException(
                'Set the request, parser list, and endpoint list before '.
                'calling dispatch()', 500);
        }

        $endpoint = $this->getEndpoint();
        try {
            $endpoint->authenticate($this->request);
            $safe_input = $this->parseInput()
                ->addData($this->getUriData())
                ->addData($this->getQueryStringData())
                ->validate($endpoint);

            $response = $endpoint->execute($safe_input);
        } catch (\Throwable $e) {
            $response = $endpoint->handleException($e);
        }
        return $this->executeResponseMiddleware($response);
    }

    /**
     * Executes any provided response middleware callbacks previously added
     * with `addResponseMiddleware()`. This wraps itself in a callback so that
     * each successive callback may be executed. Each middleware may
     * short-circuit all remaining callbacks, but still must return
     * a ResponseInterface object
     *
     * @param ResponseInterface the response so far
     * @return ResponseInterface the response after any additional processing
     */
    private function executeResponseMiddleware(
        ResponseInterface $response
    ): ResponseInterface {
        // Out of middlewares to run
        if (!$this->response_middleware) {
            return $response;
        }
        // Get the next in line and dispatch
        $middleware = array_shift($this->response_middleware);
        return $middleware($response, function(ResponseInterface $response) {
            return $this->executeResponseMiddleware($response);
        });
    }

    /**
     * Parse the raw input body based on the content type
     *
     * @return ParsedInput the parsed input data
     */
    private function parseInput(): ParsedInput
    {
        $data = [];
        // Presence of Content-type header indicates PUT/POST; parse it. We
        // don't use $_POST because additional content types are supported.
        // Since PSR-7 doesn't specify parsing the body of most MIME-types,
        // we'll hand off to our own set of parsers.
        $cth = $this->request->getHeader('Content-type');
        if ($cth) {
            list($parser_class) = (new ClassMapper($this->parser_list))
                ->search($cth[0]);
            if (!$parser_class) {
                throw new OutOfBoundsException('Unsupported Content-type', 400);
            }
            $parser = new $parser_class;
            $data = $parser->parse((string)$this->request->getBody());
        }
        return new ParsedInput($data);
    }

    /**
     * Find and instanciate the endpoint based on the request.
     *
     * @return Interfaces\EndpointInterface the routed endpoint
     */
    private function getEndpoint(): Interfaces\EndpointInterface
    {
        list($class, $uri_data) = (new ClassMapper($this->endpoint_list))
            ->filter(strtoupper($this->request->getMethod()))
            ->search($this->request->getUri()->getPath());
        if (!$class) {
            throw new OutOfBoundsException('Endpoint not found', 404);
        }
        // Conceivably, we could use reflection to ensure the found class
        // adheres to the interface; in practice, the built route is already
        // doing the filtering so this should be redundant.
        $this->setUriData(new ParsedInput($uri_data));
        if ($this->container && $this->container->has($class)) {
            return $this->container->get($class);
        }
        return new $class;
    }

    private function setUriData(ParsedInput $uri_data): self
    {
        $this->uri_data = $uri_data;
        return $this;
    }

    private function getUriData(): ParsedInput
    {
        return $this->uri_data;
    }

    private function getQueryStringData(): ParsedInput
    {
        $uri = $this->request->getUri();
        $query = $uri->getQuery();
        $data = [];
        parse_str($query, $data);
        return new ParsedInput($data);
    }

}

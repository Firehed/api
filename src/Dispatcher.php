<?php

namespace Firehed\API;

use Firehed\Common\ClassMapper;
use Firehed\Input\Containers\ParsedInput;
use Firehed\Input\Exceptions\InputException;
use Psr\Http\Message\RequestInterface;
use UnexpectedValueException;
use Zend\Diactoros\Response\JsonResponse;

class Dispatcher
{

    private $container = [];
    private $endpoint_list;
    private $error_handler;
    private $parser_list;
    private $request;
    private $uri_data;

    /**
     * Provide a DI Container/Service Locator class or array. During
     * dispatching, this structure will be queried for the routed endpoint by
     * the fully-qualified class name. If the container has a class at that
     * key, it will be used during execution; if not, the default behavior is
     * to automatically instanciate it.
     *
     * @param array|ArrayAccess Container
     * @return self
     */
    public function setContainer($container)
    {
        if (!(is_array($container) || ($container instanceof \ArrayAccess))) {
            throw new UnexpectedValueException(
                'Only arrays and classes implementing ArrayAccess may be provided');
        }
        $this->container = $container;
        return $this;
    }

    /**
     * Configure a callback when an error condition occurs. ... more info
     * coming
     *
     *
     * @param callable error handler
     * @return self
     *
    public function setErrorHandler(callable $handler)
    {
        $this->error_handler = $handler;
        return $this;
    }
    *** Not useful yet ***/

    /**
     * Inject the request
     *
     * @param RequestInterface The request
     * @return self
     */
    public function setRequest(RequestInterface $request)
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
    public function setParserList($parser_list)
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
    public function setEndpointList($endpoint_list)
    {
        $this->endpoint_list = $endpoint_list;
        return $this;
    }

    /**
     * @param int HTTP status code
     * @return Psr\Http\Message\ResponseInterface
     */
    protected function error($http_code)
    {
        return new JsonResponse([
            'error' => [
                'message' => 'MESSAGE',
            ],
        ], $http_code);
    }

    /**
     * Execute the request
     *
     * @return \Psr\Http\Message\ResponseInterface the completed HTTP response
     */
    public function dispatch()
    {
        if (null === $this->request ||
            null === $this->parser_list ||
            null === $this->endpoint_list) {
            return $this->error(500);
        }

        try {
            $endpoint = $this->getEndpoint();
            $safe_input = $this->parseInput()
                ->addData($this->getUriData())
                ->validate($endpoint);
        }
        catch (HTTPException $e) {
            return $this->error($e->getCode());
        }
        catch (InputException $e) {
            return $this->error(400);
        }

        return $endpoint->execute($safe_input);
    }

    /**
     * Parse the raw input body based on the content type
     *
     * @return ParsedInput the parsed input data
     */
    private function parseInput() {
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
                throw new HTTPException('Unsupported Content-type', 400);
            }
            $parser = new $parser_class;
            $data = $parser->parse($this->request->getBody());
        }
        return new ParsedInput($data);
    }

    /**
     * Find and instanciate the endpoint based on the request.
     *
     * @return Interfaces\EndpointInterface the routed endpoint
     * @throws HTTPException if no endpoint matched the URI and HTTP Method
     * from the request
     */
    private function getEndpoint()
    {
        list($class, $uri_data) = (new ClassMapper($this->endpoint_list))
            ->filter(strtoupper($this->request->getMethod()))
            ->search($this->request->getUri()->getPath());
        if (!$class) {
            throw new HTTPException('Endpoint not found', 404);
        }
        // Conceivably, we could use reflection to ensure the found class
        // adheres to the interface; in practice, the built route is already
        // doing the filtering so this should be redundant.
        $this->setUriData(new ParsedInput($uri_data));
        if (isset($this->container[$class])) {
            return $this->container[$class];
        }
        return new $class;
    }

    private function setUriData(ParsedInput $uri_data)
    {
        $this->uri_data = $uri_data;
        return $this;
    }

    private function getUriData()
    {
        return $this->uri_data;
    }

}

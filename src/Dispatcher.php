<?php

namespace Firehed\API;

use Psr\Http\Message\RequestInterface;
use Firehed\Common\ClassMapper;
use Firehed\Input\Containers\ParsedInput;
use Firehed\Input\Exceptions\InputException;

use Zend\Diactoros\Response\JsonResponse;

class Dispatcher
{

    private $endpoint_list;
    private $error_handler;
    private $parser_list;
    private $request;

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

    public function dispatch()
    {
        if (!$this->request || !$this->parser_list || !$this->endpoint_list) {
            return $this->error(500);
        }

        $data = [];
        // Presence of Content-type header indicates PUT/POST; parse it. We
        // don't use $_POST because additional content types are supported.
        $cth = $this->request->getHeader('Content-type');
        if ($cth) {
            list($parser_class) = (new ClassMapper($this->parser_list))
                ->search($cth[0]);
            if (!$parser_class) {
                // 400 Bad Request
                return $this->error(400);
            }
            $parser = new $parser_class;
            $data = $parser->parse($this->request->getBody());
        }

        $parsed_input = new ParsedInput($data);

        list($class, $uri_data) = (new ClassMapper($this->endpoint_list))
            ->filter(strtoupper($this->request->getMethod()))
            ->search($this->request->getUri()->getPath());
        if (!$class) {
            // Handle error: 404
            return $this->error(404);
        }
        $parsed_input->addData(new ParsedInput($uri_data));

        $endpoint = new $class;
        try {
            $safe_input = $parsed_input->validate($endpoint);
        }
        catch (InputException $e) {
            return $this->error(400);
        }
        // something something middleware
        return $endpoint->execute($safe_input);

    }
}

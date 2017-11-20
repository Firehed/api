<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Provides simple response-building methods for HTML, JSON, and plain text
 * bodies.
 */
trait ResponseBuilder
{

    /**
     * Returns an empty-body response
     *
     * @param int $code HTTP Status code (deafult 204)
     *
     * @return ResponseInterface a PSR-7 response
     */
    public function emptyResponse(int $code = 204): ResponseInterface
    {
        return new Response\EmptyResponse($code);
    }

    /**
     * Treats the body as HTML and builds a response with an HTML Content-type
     * header
     *
     * @param string $body The HTML to send
     * @param int $code HTTP Status code (deafult 200)
     *
     * @return ResponseInterface a PSR-7 response
     */
    public function htmlResponse(string $body, int $code = 200): ResponseInterface
    {
        return new Response\HtmlResponse($body, $code);
    }

    /**
     * JSON-encodes the provided data and builds a response with with a JSON
     * Content-type header
     *
     * @param mixed $data Any JSON-encodable data
     * @param int $code HTTP Status code (deafult 200)
     *
     * @return ResponseInterface a PSR-7 response
     */
    public function jsonResponse($data, int $code = 200): ResponseInterface
    {
        return new Response\JsonResponse($data, $code);
    }

    /**
     * Builds a plaintext response with the provided string
     *
     * @param string $body The text to send
     * @param int $code HTTP Status code (deafult 200)
     *
     * @return ResponseInterface a PSR-7 response
     */
    public function textResponse(string $body, int $code = 200): ResponseInterface
    {
        return new Response\TextResponse($body, $code);
    }
}

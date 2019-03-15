<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

class ResponseRenderer
{
    public static function render(ResponseInterface $response)
    {
        $renderer = new ResponseRenderer();
        $renderer->sendHeaders($response);
        $renderer->sendBody($response);
    }

    public function sendHeaders(ResponseInterface $response)
    {
        // Send HTTP code
        header(sprintf(
            "HTTP/%s %s %s",
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));
        // Additional headers
        foreach ($response->getHeaders() as $key => $values) {
            foreach ($values as $value) {
                header(sprintf("%s: %s", $key, $value), false);
            }
        }
    }

    public function sendBody(ResponseInterface $response)
    {
        echo $response->getBody();
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

class ResponseRenderer
{
    /** @var ResponseInterface */
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function sendHeaders(): void
    {
        // Send HTTP code
        header(sprintf(
            "HTTP/%s %s %s",
            $this->response->getProtocolVersion(),
            $this->response->getStatusCode(),
            $this->response->getReasonPhrase()
        ));
        // Additional headers
        foreach ($this->response->getHeaders() as $key => $values) {
            foreach ($values as $value) {
                header(sprintf("%s: %s", $key, $value), false);
            }
        }
    }

    public function sendBody(): void
    {
        echo $this->response->getBody();
    }
}

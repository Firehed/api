<?php

declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

/**
 * Takes a PSR-7 Response and outputs all headers and body. This should be the
 * very last thing done in request processing.
 */
function renderResponse(ResponseInterface $response)
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
    // And then the body
    echo $response->getBody();
}

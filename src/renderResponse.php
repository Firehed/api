<?php

declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

/**
 * Takes a PSR-7 Response and outputs all headers and body. This should be the
 * very last thing done in request processing.
 */
function renderResponse(ResponseInterface $response): void
{
    $renderer = new ResponseRenderer($response);
    $renderer->sendHeaders();
    $renderer->sendBody();
}

<?php

declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

/**
 * Takes a PSR-7 Response and outputs all headers and body. This should be the
 * very last thing done in request processing.
 *
 * @deprecated 3.1.0 Use ResponseRenderer instead
 */
if (!function_exists(__NAMESPACE__.'\renderResponse')) {
    function renderResponse(ResponseInterface $response)
    {
        ResponseRenderer::render($response);
    }
}
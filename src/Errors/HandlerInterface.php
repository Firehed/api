<?php
declare(strict_types=1);

namespace Firehed\API\Errors;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Implementations of this interface are for application-wide error handling.
 * See the section on error handling in the README for additional information.
 */
interface HandlerInterface
{
    /**
     * Handle an exception for the given request. It is RECOMMENDED that
     * implementations use the $request's accept header to determine how best
     * to format the response.
     */
    public function handle(ServerRequestInterface $request, Throwable $t): ResponseInterface;
}

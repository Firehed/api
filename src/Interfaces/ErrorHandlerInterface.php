<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ErrorHandlerInterface
{
    /**
     * Handle an exception for the given request. It is RECOMMENDED that
     * implementations use the $request's accept header to determine how best
     * to build the response.
     */
    public function handle(ServerRequestInterface $request, Throwable $t): ResponseInterface;
}

<?php
declare(strict_types=1);

namespace Firehed\API\fixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    /** @var int */
    private $processed = 0;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->processed++;
        return $handler->handle($request);
    }

    public function getProcessedCount(): int
    {
        return $this->processed;
    }
}

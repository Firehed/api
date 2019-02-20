<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Internal queue-style middleware dispatcher. This IS a stateful component,
 * but it is recreated on every request so that middlware isn't lost if the
 * dispatcher is reused (e.g. as a long-running server)
 * @internal
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private $middleware;

    /** @var RequestHandlerInterface */
    private $fallback;

    /**
     * @var RequestHandlerInterface $fallback The default handler to call once
     * all middleware is exhausted
     * @var MiddlewareInterface[] $middleware The list of middlewares to
     * execute
     */
    public function __construct(RequestHandlerInterface $fallback, array $middleware)
    {
        $this->fallback = $fallback;
        $this->middleware = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!count($this->middleware)) {
            return $this->fallback->handle($request);
        }
        $middleware = array_shift($this->middleware);
        return $middleware->process($request, $this);
    }
}

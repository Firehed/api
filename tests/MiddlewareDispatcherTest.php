<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @coversDefaultClass Firehed\API\MiddlewareDispatcher
 */
class MiddlewareDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @covers ::__construct */
    public function testConstruct()
    {
        $fallback = $this->createMock(RequestHandlerInterface::class);
        $mw = $this->createMock(MiddlewareInterface::class);

        $dispatcher = new MiddlewareDispatcher($fallback, [$mw]);
        $this->assertInstanceOf(RequestHandlerInterface::class, $dispatcher);
    }

    /** @covers ::handle */
    public function testMiddlewareIsExecutedInOrder()
    {
        // This is an object cast to ensure proper by-reference handling in all
        // of the mock callbacks
        $state = (object) [
            'mw1Run' => false,
            'mw2Run' => false,
            'fallbackRun' => false,
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $mw1 = $this->createMock(MiddlewareInterface::class);
        $mw1->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) use ($state) {
                $this->assertFalse($state->mw1Run, 'MW1 should not have run');
                $this->assertFalse($state->mw2Run, 'MW2 should not have run');
                $this->assertFalse($state->fallbackRun, 'Fallback should not have run');
                $state->mw1Run = true;
                return $handler->handle($request);
            });

        $mw2 = $this->createMock(MiddlewareInterface::class);
        $mw2->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($request, $handler) use ($state) {
                $this->assertTrue($state->mw1Run, 'MW1 should have run');
                $this->assertFalse($state->mw2Run, 'MW2 should not have run');
                $this->assertFalse($state->fallbackRun, 'Fallback should not have run');
                $state->mw2Run = true;
                return $handler->handle($request);
            });


        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($request) use ($state, $response) {
                $this->assertTrue($state->mw1Run, 'MW1 should have run');
                $this->assertTrue($state->mw2Run, 'MW2 should have run');
                $this->assertFalse($state->fallbackRun, 'Fallback should not have run');
                $state->fallbackRun = true;
                return $response;
            });

        $dispatcher = new MiddlewareDispatcher($fallback, [$mw1, $mw2]);
        $this->assertSame($response, $dispatcher->handle($request));

        $this->assertTrue($state->mw1Run, 'MW1 should have run');
        $this->assertTrue($state->mw2Run, 'MW2 should have run');
        $this->assertTrue($state->fallbackRun, 'Fallback should have run');
    }

    /** @covers ::handle */
    public function testMiddlewareCanShortCircuit()
    {
        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->never())
            ->method('handle');

        $response = $this->createMock(ResponseInterface::class);

        $shortCircuitMiddleware = $this->createMock(MiddlewareInterface::class);
        $shortCircuitMiddleware->expects($this->once())
            ->method('process')
            ->willReturn($response);

        $dispatcher = new MiddlewareDispatcher($fallback, [$shortCircuitMiddleware]);

        $request = $this->createMock(ServerRequestInterface::class);

        $this->assertSame($response, $dispatcher->handle($request));
    }

    /** @covers ::handle */
    public function testWithNoMiddleware()
    {
        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->once())
            ->method('handle');

        $dispatcher = new MiddlewareDispatcher($fallback, []);

        $request = $this->createMock(ServerRequestInterface::class);

        $response = $dispatcher->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}

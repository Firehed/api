<?php
declare(strict_types=1);

namespace Firehed\API;

use ErrorException;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass Firehed\API\ErrorHandler
 * @covers ::<protected>
 * @covers ::<private>
 */
class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(
            ErrorHandler::class,
            new ErrorHandler($this->createMock(LoggerInterface::class))
        );
    }

    /**
     * @covers ::handleThrowable
     * @runInSeparateProcess
     */
    public function testHandleThrowable(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('error');

        $handler = new ErrorHandler($logger);
        $handler->handleThrowable(new Exception());
    }

    /**
     * @covers ::handleError
     */
    public function testHandleError(): void
    {
        $handler = new ErrorHandler($this->createMock(LoggerInterface::class));
        $this->expectException(ErrorException::class);
        $handler->handleError(\E_ERROR, 'Some error', __FILE__, __LINE__);
    }

    /**
     * @covers ::handleError
     * @doesNotPerformAssertions
     */
    public function testHandleErrorDoesNotThrowWithErrorReportingDisabled(): void
    {
        $handler = new ErrorHandler($this->createMock(LoggerInterface::class));
        // @ turns error_reporting() to 0 for the next line. The error handler
        // should respect this.
        @$handler->handleError(\E_ERROR, 'Some error', __FILE__, __LINE__);
    }
}

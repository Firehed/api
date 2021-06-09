<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @coversDefaultClass Firehed\API\HandlesOwnErrorsFixture
 * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases
 */
class HandlesOwnErrorsTestCasesTest extends \PHPUnit\Framework\TestCase
{
    use HandlesOwnErrorsTestCases;

    /** @var bool */
    private $endpointShouldThrow = true;

    public function setUp(): void
    {
        $this->setAllowHandleExceptionToRethrow(true);
        $this->endpointShouldThrow = true;
    }

    protected function getErrorHandlingEndpoint(): HandlesOwnErrorsInterface
    {
        $mock = $this->createMock(HandlesOwnErrorsInterface::class);
        $mock->method('handleException')
            ->willReturnCallback(function ($e) {
                if ($this->endpointShouldThrow) {
                    throw $e;
                } else {
                    return $this->createMock(ResponseInterface::class);
                }
            });
        return $mock;
    }

    /**
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::exceptionsToHandle
     */
    public function testExceptionsToHandle(): void
    {
        $data = $this->exceptionsToHandle();
        foreach ($data as $testCase) {
            list($testParam) = $testCase;
            $this->assertInstanceOf(Throwable::class, $testParam);
        }
    }

    /**
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::setAllowHandleExceptionToRethrow
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::testHandleException
     * @dataProvider exceptionsToHandle
     */
    public function testDefaultHandling(Throwable $e): void
    {
        $this->setAllowHandleExceptionToRethrow(false);
        $this->expectException(get_class($e));
        $this->testHandleException($e);
    }

    /**
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::testHandleException
     */
    public function testSuccessfullyHandlingException(): void
    {
        $this->endpointShouldThrow = false;
        $ex = new \Exception();
        $this->testHandleException($ex);
    }

    /**
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::testHandleException
     */
    public function testHandlingRethrownException(): void
    {
        $this->endpointShouldThrow = true;
        $this->setAllowHandleExceptionToRethrow(true);
        $this->testHandleException(new \Exception());
    }
}

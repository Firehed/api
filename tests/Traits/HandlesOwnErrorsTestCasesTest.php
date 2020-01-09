<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @coversDefaultClass Firehed\API\HandlesOwnErrorsFixture
 * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::<protected>
 * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::<private>
 */
class HandlesOwnErrorsTestCasesTest extends \PHPUnit\Framework\TestCase
{
    use HandlesOwnErrorsTestCases;

    private $endpointShouldThrow = true;

    public function setUp(): void
    {
        $this->setAllowHandleExceptionToRethrow(true);
        $this->endpointShouldThrow = true;
    }

    protected function getEndpoint(): HandlesOwnErrorsInterface
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
    public function testExceptionsToHandle()
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
    public function testDefaultHandling(Throwable $e)
    {
        $this->setAllowHandleExceptionToRethrow(false);
        $this->expectException(get_class($e));
        $this->testHandleException($e);
    }

    /**
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::testHandleException
     */
    public function testSuccessfullyHandlingException()
    {
        $this->endpointShouldThrow = false;
        $ex = new \Exception();
        $this->testHandleException($ex);
    }

    /**
     * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::testHandleException
     */
    public function testHandlingRethrownException()
    {
        $this->endpointShouldThrow = true;
        $this->setAllowHandleExceptionToRethrow(true);
        $this->testHandleException(new \Exception());
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Throwable;

/**
 * @coversDefaultClass Firehed\API\EndpointFixture
 * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::<protected>
 * @covers Firehed\API\Traits\HandlesOwnErrorsTestCases::<private>
 */
class HandlesOwnErrorsTestCasesTest extends \PHPUnit\Framework\TestCase
{
    use HandlesOwnErrorsTestCases;

    public function setUp()
    {
        $this->setAllowHandleExceptionToRethrow(true);
    }

    protected function getEndpoint(): HandlesOwnErrorsInterface
    {
        $mock = $this->createMock(HandlesOwnErrorsInterface::class);
        $mock->method('handleException')
            ->willReturnCallback(function ($e) {
                throw $e;
            });
        return $mock;
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
    public function testRedundantlyBecauseTraitApplicationIsWierd()
    {
        $ex = new \Exception();
        $this->testHandleException($ex);
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

trait HandlesOwnErrorsTestCases
{
    /** @var bool */
    private $handleExceptionMayRethrow = false;

    abstract protected function getEndpoint(): HandlesOwnErrorsInterface;

    public function setAllowHandleExceptionToRethrow(bool $allowed): void
    {
        $this->handleExceptionMayRethrow = $allowed;
    }

    /**
     * @covers ::handleException
     * @dataProvider exceptionsToHandle
     */
    public function testHandleException(Throwable $e): void
    {
        try {
            $response = $this->getEndpoint()->handleException($e);
            $this->assertInstanceOf(
                ResponseInterface::class,
                $response,
                'handleException() did not return a PSR7 response'
            );
        } catch (Throwable $thrown) {
            if ($this->handleExceptionMayRethrow) {
                $this->assertSame($e, $thrown, 'A different exception was thrown');
            } else {
                throw $thrown;
            }
        }
    }

    /**
     * Data Provider for testHandleException. To test additional exceptons,
     * alias this method during import and extend in the using class; i.e.:
     *
     * ```php
     * class MyTest extends PHPUnit\Framework\TestCase {
     *     use Firehed\API\EndpointTestTrait {
     *         exceptionsToTest as baseExceptions;
     *     }
     *     public function exceptionsToTest() {
     *         $cases = $this->baseExceptions();
     *         $cases[] = [new SomeOtherException()];
     *         return $cases;
     *      }
     *  }
     *  ```
     *
     *  @return Throwable[][]
     */
    public function exceptionsToHandle(): array
    {
        return [
            [new \Exception()],
                [new \ErrorException()],
                [new \LogicException()],
                    [new \BadFunctionCallException()],
                        [new \BadMethodCallException()],
                    [new \DomainException()],
                    [new \InvalidArgumentException()],
                    [new \LengthException()],
                    [new \OutOfRangeException()],
                [new \RuntimeException()],
                    [new \OutOfBoundsException()],
                    [new \OverflowException()],
                    [new \RangeException()],
                    [new \UnderflowException()],
                    [new \UnexpectedValueException()],
            // PHP7: Add new Error exceptions
            [new \Error()],
                [new \ArithmeticError()],
                [new \AssertionError()],
                [new \DivisionByZeroError()],
                [new \ParseError()],
                [new \TypeError()],
        ];
    }
}

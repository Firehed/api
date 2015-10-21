<?php

declare(strict_types=1);

namespace Firehed\API;

use Firehed\Input\ {
    Interfaces\ValidationInterface,
    ValidationTestTrait
};

/**
 * Default test cases to be run against any object implementing
 * EndpointInterface. This amounts to glorified type-hinting, but still
 * provides valuable automated coverage that would otherwise only be available
 * at runtime
 */
trait EndpointTestTrait
{

    use ValidationTestTrait;

    /**
     * Get the endpoint under test
     * @return Interfaces\EndpointInterface
     */
    abstract protected function getEndpoint(): Interfaces\EndpointInterface;

    /**
     * Because EndpointInterface extends ValidationInterface, provide the same
     * object to the parent handler
     *
     * @return \Firehed\Input\Interfaces\ValidationInterface
     */
    protected function getValidation(): ValidationInterface
    {
        return $this->getEndpoint();
    }

    /** @covers ::getUri */
    public function testGetUri()
    {
        $endpoint = $this->getEndpoint();
        $uri = $endpoint->getUri();
        $this->assertInternalType('string',
            $uri,
            'getUri did not return a string');
    }

    /** @covers ::getMethod */
    public function testGetMethod()
    {
        $method = $this->getEndpoint()->getMethod();
        $this->assertInstanceOf('Firehed\API\Enums\HTTPMethod',
            $method,
            'getMethod did not return an HTTPMethod enum');
    }

    /**
     * @covers ::handleException
     * @dataProvider exceptionsToHandle
     */
    public function testHandleException(\Throwable $e)
    {
        $response = $this->getEndpoint()->handleException($e);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface',
            $response,
            'handleException() did not return a PSR7 response');
    }

    /**
     * Data Provider for testHandleException. To test additional exceptons,
     * alias this method during import and extend in the using class; i.e.:
     *
     * ```php
     * class MyTest extends PHPUnit_Framework_TestCase {
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
     *  @return array<array<Exception>>
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

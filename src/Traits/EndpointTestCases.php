<?php

declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Interfaces\ValidationInterface;
use Firehed\Input\ValidationTestTrait;

/**
 * Default test cases to be run against any object implementing
 * EndpointInterface. This amounts to glorified type-hinting, but still
 * provides valuable automated coverage that would otherwise only be available
 * at runtime
 */
trait EndpointTestCases
{

    use ValidationTestTrait;

    /**
     * Get the endpoint under test
     * @return Interfaces\EndpointInterface
     */
    abstract protected function getEndpoint(): EndpointInterface;

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

    /**
     * @covers ::getUri
     * @dataProvider uris
     *
     * @param string $uri The URI to match against
     * @param bool $match Whether or not the provied URI should match
     * @param array $expectedMatches Named captures in a positive match
     */
    public function testGetUri(string $uri, bool $match, array $expectedMatches)
    {
        $endpoint = $this->getEndpoint();
        $this->assertInternalType(
            'string',
            $endpoint->getUri(),
            'getUri did not return a string'
        );

        $pattern = '#^' . $endpoint->getUri() . '$#';

        $this->assertSame($match, (bool) preg_match($pattern, $uri, $matches));
        foreach ($expectedMatches as $key => $value) {
            $this->assertTrue(array_key_exists($key, $matches));
            $this->assertSame($value, $matches[$key]);
        }
    }

    public function uris(): array
    {
        $good = $this->goodUris();
        $bad = $this->badUris();
        if (!$good || !$bad) {
            $message = <<<'TEXT'
No URIs provided to validate. To provide URIs, add methods `goodUris()` and
`badUris()` to your test case class. `goodUris()` should return a map of URI to
named captures; e.g. ['/some/uri' => ['paramName' => 'uri']]. `badURIs()`
should return an array of strings; e.g. ['/some/non/matching/path'].
TEXT;
            $this->markTestSkipped($message);
        }
        return array_merge(
            array_map(function ($uri, $matches) {
                return [$uri, true, $matches];
            }, array_keys($good), array_values($good)),
            array_map(function ($uri) {
                return [$uri, false, []];
            }, $bad)
        );
    }

    protected function goodUris(): array
    {
        return [];
    }

    protected function badUris(): array
    {
        return [];
    }

    /** @covers ::getMethod */
    public function testGetMethod()
    {
        $method = $this->getEndpoint()->getMethod();
        $this->assertInstanceOf(
            'Firehed\API\Enums\HTTPMethod',
            $method,
            'getMethod did not return an HTTPMethod enum'
        );
    }

    /**
     * @covers ::handleException
     * @dataProvider exceptionsToHandle
     */
    public function testHandleException(\Throwable $e)
    {
        $response = $this->getEndpoint()->handleException($e);
        $this->assertInstanceOf(
            'Psr\Http\Message\ResponseInterface',
            $response,
            'handleException() did not return a PSR7 response'
        );
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

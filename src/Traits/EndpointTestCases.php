<?php

declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Containers;
use Firehed\Input\Interfaces\ValidationInterface;
use Firehed\Input\SafeInputTestTrait;
use Firehed\Input\ValidationTestTrait;

/**
 * Default test cases to be run against any object implementing
 * EndpointInterface. This amounts to glorified type-hinting, but still
 * provides valuable automated coverage that would otherwise only be available
 * at runtime
 */
trait EndpointTestCases
{
    use SafeInputTestTrait;
    use ValidationTestTrait;

    /**
     * Get the endpoint under test
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
     * Takes a "request body" and runs it through the actual endpoint
     * validation process, returning a SafeInput that can be passed directly to
     * `execute()` during a test case. This helps ensure that any data
     * transformations that take place during request validation are applied,
     * and can additionally help when writing tests to assert that input
     * validation is defined correctly.
     *
     * @param array<string, mixed> $parsedInput The parsed request input (e.g.
     *                                          $_POST + $_GET, or json_decode(php://input)
     */
    protected function getSafeInput(array $parsedInput): Containers\SafeInput
    {
        return (new Containers\ParsedInput($parsedInput))
            ->validate($this->getEndpoint());
    }

    /**
     * @covers ::getUri
     * @dataProvider uris
     *
     * @param string $uri The URI to match against
     * @param bool $match Whether or not the provied URI should match
     * @param array<string, string> $expectedMatches Named captures in a positive match
     */
    public function testGetUri(string $uri, bool $match, array $expectedMatches): void
    {
        $endpoint = $this->getEndpoint();
        $this->assertIsString(
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

    /** @return mixed[] */
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

    /**
     * @return array<string, array<string, string>>
     */
    protected function goodUris(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function badUris(): array
    {
        return [];
    }

    /** @covers ::getMethod */
    public function testGetMethod(): void
    {
        $method = $this->getEndpoint()->getMethod();
        $this->assertInstanceOf(
            'Firehed\API\Enums\HTTPMethod',
            $method,
            'getMethod did not return an HTTPMethod enum'
        );
    }
}

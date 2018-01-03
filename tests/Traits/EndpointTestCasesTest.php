<?php

declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\EndpointFixture;
use Firehed\API\Interfaces\EndpointInterface;
use Throwable;

/**
 * @coversDefaultClass Firehed\API\EndpointFixture
 * @covers Firehed\API\Traits\EndpointTestCases::<protected>
 * @covers Firehed\API\Traits\EndpointTestCases::<private>
 * @covers Firehed\API\Traits\EndpointTestCases::getValidation
 * @covers Firehed\API\Traits\EndpointTestCases::testGetUri
 * @covers Firehed\API\Traits\EndpointTestCases::testGetMethod
 * @covers Firehed\API\Traits\EndpointTestCases::testHandleException
 */
class EndpointTestCasesTest extends \PHPUnit\Framework\TestCase
{

    use EndpointTestCases {
        goodUris as baseGoodUris;
        badUris as baseBadUris;
    }

    protected function getEndpoint(): EndpointInterface
    {
        return new EndpointFixture();
    }

    /**
     * @covers Firehed\API\Traits\EndpointTestCases::exceptionsToHandle
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
     * @covers Firehed\API\Traits\EndpointTestCases::uris
     */
    public function testUris()
    {
        $data = $this->uris();
        foreach ($data as $testCase) {
            list($uri, $shouldMatch, $matches) = $testCase;
            $this->assertInternalType('string', $uri);
            $this->assertInternalType('bool', $shouldMatch);
            $this->assertInternalType('array', $matches);
        }
    }

    public function goodUris(): array
    {
        return $this->baseGoodUris() + [
            '/user/1' => ['id' => '1'],
            '/user/10' => ['id' => '10'],
            '/user/3' => ['id' => '3'],
            '/user/134098435089225' => ['id' => '134098435089225'],
        ];
    }

    public function badUris(): array
    {
        return $this->baseBadUris() + [
            '/',
            '/0/user/',
            '/3/user',
            'user/3',
            '/user',
            '/user/',
            '/user/0',
            '/user/01234',
            '/user/username',
        ];
    }
}

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
class EndpointTestTraitTest extends \PHPUnit\Framework\TestCase
{

    use EndpointTestCases;

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
            $this->assertInstanceOf(Throwable::Class, $testParam);
        }
    }

    /**
     * @covers Firehed\API\Traits\EndpointTestCases::uris
     */
    public function goodUris(): array
    {
        return [
            '/user/1' => ['id' => '1'],
            '/user/10' => ['id' => '10'],
            '/user/3' => ['id' => '3'],
            '/user/134098435089225' => ['id' => '134098435089225'],
        ];
    }

    /**
     * @covers Firehed\API\Traits\EndpointTestCases::uris
     */
    public function badUris(): array
    {
        return [
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

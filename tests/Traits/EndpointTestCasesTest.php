<?php

declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\EndpointFixture;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Containers\SafeInput;
use Throwable;

/**
 * @covers Firehed\API\EndpointFixture
 * @covers Firehed\API\Traits\EndpointTestCases
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
     * @covers Firehed\API\Traits\EndpointTestCases::getSafeInput
     */
    public function testGetSafeInput(): void
    {
        $data = $this->getSafeInput([
            'id' => '123',
            'shortstring' => 'short',
        ]);
        $this->assertInstanceOf(SafeInput::class, $data);
        $this->assertSame(123, $data['id'], 'id should have been int from validation cast');
        $this->assertSame('short', $data['shortstring'], 'shortstring was wrong');
    }

    /**
     * @covers Firehed\API\Traits\EndpointTestCases::uris
     */
    public function testUris(): void
    {
        $data = $this->uris();
        foreach ($data as $testCase) {
            list($uri, $shouldMatch, $matches) = $testCase;
            $this->assertIsString($uri);
            $this->assertIsBool($shouldMatch);
            $this->assertIsArray($matches);
        }
    }

    /** @return array<string, string[]> */
    public function goodUris(): array
    {
        return $this->baseGoodUris() + [
            '/user/1' => ['id' => '1'],
            '/user/10' => ['id' => '10'],
            '/user/3' => ['id' => '3'],
            '/user/134098435089225' => ['id' => '134098435089225'],
        ];
    }

    /** @return string[] */
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

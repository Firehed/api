<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @covers Firehed\API\Traits\Request\Get
 */
class GetTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMethod(): void
    {
        $obj = new class {
            use Get;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::GET(),
            $obj->getMethod(),
            'getMethod did not return HTTP GET'
        );
    }
}

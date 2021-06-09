<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @covers Firehed\API\Traits\Request\Patch
 */
class PatchTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMethod(): void
    {
        $obj = new class {
            use Patch;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::PATCH(),
            $obj->getMethod(),
            'getMethod did not return HTTP PATCH'
        );
    }
}

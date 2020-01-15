<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @coversDefaultClass Firehed\API\Traits\Request\Patch
 * @covers ::<protected>
 * @covers ::<private>
 */
class PatchTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getMethod
     */
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

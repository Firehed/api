<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @coversDefaultClass Firehed\API\Traits\Request\Delete
 * @covers ::<protected>
 * @covers ::<private>
 */
class DeleteTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use Delete;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::DELETE(),
            $obj->getMethod(),
            'getMethod did not return HTTP DELETE'
        );
    }
}

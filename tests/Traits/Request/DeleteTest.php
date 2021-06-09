<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @covers Firehed\API\Traits\Request\Delete
 */
class DeleteTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMethod(): void
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

<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\DeleteRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class DeleteRequestTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use DeleteRequest;
        };
        $this->expectDeprecation();
        $obj->getMethod();
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\PutRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class PutRequestTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use PutRequest;
        };
        $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        $obj->getMethod();
    }
}

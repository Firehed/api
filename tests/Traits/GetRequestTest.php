<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\GetRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class GetRequestTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use GetRequest;
        };
        $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        $obj->getMethod();
    }
}

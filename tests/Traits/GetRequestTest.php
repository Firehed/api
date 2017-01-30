<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\GetRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class GetRequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use GetRequest;
        };
        $this->expectException(\PHPUnit_Framework_Error_Deprecated::class);
        $obj->getMethod();
    }


}

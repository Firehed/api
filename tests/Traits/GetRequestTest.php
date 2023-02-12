<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @covers Firehed\API\Traits\GetRequest
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
        $this->expectDeprecation();
        $obj->getMethod();
    }
}

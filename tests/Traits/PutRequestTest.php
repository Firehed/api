<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @covers Firehed\API\Traits\PutRequest
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
        $this->expectDeprecation();
        $obj->getMethod();
    }
}

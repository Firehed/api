<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @covers Firehed\API\Traits\PostRequest
 */
class PostRequestTest extends \PHPUnit\Framework\TestCase
{


    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use PostRequest;
        };
        $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        $obj->getMethod();
    }
}

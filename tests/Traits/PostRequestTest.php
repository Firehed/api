<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\PostRequest
 * @covers ::<protected>
 * @covers ::<private>
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
        $this->expectDeprecation();
        $obj->getMethod();
    }
}

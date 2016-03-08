<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\PostRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class PostRequestTest extends \PHPUnit_Framework_TestCase
{


    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use PostRequest;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::POST(),
            $obj->getMethod(),
            'getMethod did not return HTTP POST');
    }

}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @coversDefaultClass Firehed\API\Traits\Request\Post
 * @covers ::<protected>
 * @covers ::<private>
 */
class PostTest extends \PHPUnit_Framework_TestCase
{


    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use Post;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::POST(),
            $obj->getMethod(),
            'getMethod did not return HTTP POST');
    }

}

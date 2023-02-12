<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @covers Firehed\API\Traits\Request\Post
 */
class PostTest extends \PHPUnit\Framework\TestCase
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
            'getMethod did not return HTTP POST'
        );
    }
}

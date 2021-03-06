<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @covers Firehed\API\Traits\Request\Post
 */
class PostTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMethod(): void
    {
        $obj = new class {
            use Post;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::POST,
            $obj->getMethod(),
            'getMethod did not return HTTP POST'
        );
    }
}

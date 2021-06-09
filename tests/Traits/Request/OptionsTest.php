<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @covers Firehed\API\Traits\Request\Options
 */
class OptionsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMethod(): void
    {
        $obj = new class {
            use Options;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::OPTIONS(),
            $obj->getMethod(),
            'getMethod did not return HTTP OPTIONS'
        );
    }
}

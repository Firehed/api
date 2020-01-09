<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @coversDefaultClass Firehed\API\Traits\Request\Options
 * @covers ::<protected>
 * @covers ::<private>
 */
class OptionsTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getMethod
     */
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

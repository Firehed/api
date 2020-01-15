<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

/**
 * @coversDefaultClass Firehed\API\Traits\Input\NoOptional
 * @covers ::<protected>
 * @covers ::<private>
 */
class NoOptionalTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getOptionalInputs
     */
    public function testGetOptionalInputs(): void
    {
        $obj = new class {
            use NoOptional;
        };
        $this->assertSame(
            [],
            $obj->getOptionalInputs(),
            'getOptionalInputs did not return an empty array'
        );
    }
}

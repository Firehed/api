<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

/**
 * @covers Firehed\API\Traits\Input\NoOptional
 */
class NoOptionalTest extends \PHPUnit\Framework\TestCase
{
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

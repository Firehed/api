<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\NoOptionalInputs
 * @covers ::<protected>
 * @covers ::<private>
 */
class NoOptionalInputsTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getOptionalInputs
     */
    public function testGetOptionalInputs()
    {
        $obj = new class {
            use NoOptionalInputs;
        };
        $this->expectDeprecation();
        $obj->getOptionalInputs();
    }
}

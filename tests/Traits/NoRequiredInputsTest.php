<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\NoRequiredInputs
 * @covers ::<protected>
 * @covers ::<private>
 */
class NoRequiredInputsTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getRequiredInputs
     */
    public function testGetRequiredInputs()
    {
        $obj = new class {
            use NoRequiredInputs;
        };
        $this->expectDeprecation();
        $obj->getRequiredInputs();
    }
}

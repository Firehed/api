<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\PHPUnitPolyfillTrait;

/**
 * @coversDefaultClass Firehed\API\Traits\NoRequiredInputs
 */
class NoRequiredInputsTest extends \PHPUnit\Framework\TestCase
{
    use PHPUnitPolyfillTrait;

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

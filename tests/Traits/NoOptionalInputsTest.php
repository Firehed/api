<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\PHPUnitPolyfillTrait;

/**
 * @coversDefaultClass Firehed\API\Traits\NoOptionalInputs
 */
class NoOptionalInputsTest extends \PHPUnit\Framework\TestCase
{
    use PHPUnitPolyfillTrait;

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

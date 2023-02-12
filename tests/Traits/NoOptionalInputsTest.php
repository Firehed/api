<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @covers Firehed\API\Traits\NoOptionalInputs
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
        $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        $obj->getOptionalInputs();
    }
}

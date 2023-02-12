<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

/**
 * @covers Firehed\API\Traits\Input\NoRequired
 */
class NoRequiredTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::getRequiredInputs
     */
    public function testGetRequiredInputs()
    {
        $obj = new class {
            use NoRequired;
        };
        $this->assertSame(
            [],
            $obj->getRequiredInputs(),
            'getRequiredInputs did not return an empty array'
        );
    }
}

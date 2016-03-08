<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\NoRequiredInputs
 * @covers ::<protected>
 * @covers ::<private>
 */
class NoRequiredInputsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getRequiredInputs
     */
    public function testGetRequiredInputs()
    {
        $obj = new class {
            use NoRequiredInputs;
        };
        $this->assertSame([],
            $obj->getRequiredInputs(),
            'getRequiredInputs did not return an empty array');
    }

}

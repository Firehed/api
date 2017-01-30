<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

/**
 * @coversDefaultClass Firehed\API\Traits\Input\NoRequired
 * @covers ::<protected>
 * @covers ::<private>
 */
class NoRequiredTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getRequiredInputs
     */
    public function testGetRequiredInputs()
    {
        $obj = new class {
            use NoRequired;
        };
        $this->assertSame([],
            $obj->getRequiredInputs(),
            'getRequiredInputs did not return an empty array');
    }

}

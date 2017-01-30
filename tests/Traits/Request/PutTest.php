<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @coversDefaultClass Firehed\API\Traits\Request\Put
 * @covers ::<protected>
 * @covers ::<private>
 */
class PutTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use Put;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::PUT(),
            $obj->getMethod(),
            'getMethod did not return HTTP PUT');
    }


}

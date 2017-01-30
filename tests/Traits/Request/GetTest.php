<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

/**
 * @coversDefaultClass Firehed\API\Traits\Request\Get
 * @covers ::<protected>
 * @covers ::<private>
 */
class GetTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use Get;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::GET(),
            $obj->getMethod(),
            'getMethod did not return HTTP GET');
    }


}

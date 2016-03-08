<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\PutRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class PutRequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use PutRequest;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::PUT(),
            $obj->getMethod(),
            'getMethod did not return HTTP PUT');
    }


}

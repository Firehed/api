<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

/**
 * @coversDefaultClass Firehed\API\Traits\DeleteRequest
 * @covers ::<protected>
 * @covers ::<private>
 */
class DeleteRequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use DeleteRequest;
        };
        $this->assertEquals(
            \Firehed\API\Enums\HTTPMethod::DELETE(),
            $obj->getMethod(),
            'getMethod did not return HTTP DELETE');
    }

}

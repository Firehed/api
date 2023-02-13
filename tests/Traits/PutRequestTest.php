<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\PHPUnitPolyfillTrait;

/**
 * @coversDefaultClass Firehed\API\Traits\PutRequest
 */
class PutRequestTest extends \PHPUnit\Framework\TestCase
{
    use PHPUnitPolyfillTrait;

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $obj = new class {
            use PutRequest;
        };
        $this->expectDeprecation();
        $obj->getMethod();
    }
}

<?php

declare(strict_types=1);

namespace Firehed\API;

/**
 * @coversDefaultClass Firehed\API\EndpointTestTrait
 * @covers ::<protected>
 * @covers ::<private>
 */
class EndpointTestTraitTest extends \PHPUnit_Framework_TestCase
{

    use EndpointTestTrait;

    protected function getEndpoint(): Interfaces\EndpointInterface {
        return new EndpointFixture();
    }

}

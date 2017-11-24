<?php

declare(strict_types=1);

namespace Firehed\API;

/**
 * @coversNothing (Technically it uses the endpoint-testing trait to cover
 * itself, but the there's no src/ coverage)
 */
class EndpointTestTraitTest extends \PHPUnit\Framework\TestCase
{

    use EndpointTestTrait;

    protected function getEndpoint(): Interfaces\EndpointInterface
    {
        return new EndpointFixture();
    }
}

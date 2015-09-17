<?php

namespace Firehed\API;

use Firehed\Input\ValidationTestTrait;

/**
 * Default test cases to be run against any object implementing
 * EndpointInterface. This amounts to glorified type-hinting, but still
 * provides valuable automated coverage that would otherwise only be available
 * at runtime
 */
trait EndpointTestTrait
{

    use ValidationTestTrait;

    /**
     * Get the endpoint under test
     * @return Interfaces\EndpointInterface
     */
    abstract protected function getEndpoint();

    /**
     * Because EndpointInterface extends ValidationInterface, provide the same
     * object to the parent handler
     *
     * @return \Firehed\Input\Interfaces\ValidationInterface
     */
    protected function getValidation()
    {
        return $this->getEndpoint();
    }

    /** @covers ::getUri */
    public function testGetUri()
    {
        $endpoint = $this->getEndpoint();
        $uri = $endpoint->getUri();
        $this->assertInternalType('string',
            $uri,
            'getUri did not return a string');
    }

    /** @covers ::getMethod */
    public function testGetMethod()
    {
        $method = $this->getEndpoint()->getMethod();
        $this->assertInstanceOf('Firehed\API\Enums\HTTPMethod',
            $method,
            'getMethod did not return an HTTPMethod enum');
    }

}

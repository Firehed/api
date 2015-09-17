<?php

namespace Firehed\API;

/**
 * Default test cases to be run against any object implementing
 * EndpointInterface. This amounts to glorified type-hinting, but still
 * provides valuable automated coverage that would otherwise only be available
 * at runtime
 */
trait EndpointTestTrait
{

    /**
     * Get the endpoint under test
     * @return Interfaces\EndpointInterface
     */
    abstract protected function getEndpoint();

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

    /** @covers ::getRequiredInputs */
    public function testGetRequiredInputs()
    {
        $inputs = $this->getEndpoint()->getRequiredInputs();
        $this->assertInternalType('array',
            $inputs,
            'getRequiredInputs did not return an array');
        foreach ($inputs as $key => $type) {
            $this->assertInternalType('string',
                $key,
                'getRequiredInputs contains an invalid key');
            $this->assertInstanceOf('Firehed\Input\Objects\InputObject',
                $type,
                "getRequiredInputs[$key] is not an InputObject");
        }
    }

    /** @covers ::getOptionalInputs */
    public function testGetOptionalInputs()
    {
        $inputs = $this->getEndpoint()->getOptionalInputs();
        $this->assertInternalType('array',
            $inputs,
            'getOptionalInputs did not return an array');
        foreach ($inputs as $key => $type) {
            $this->assertInternalType('string',
                $key,
                'getOptionalInputs contains an invalid key');
            $this->assertInstanceOf('Firehed\Input\Objects\InputObject',
                $type,
                "getOptionalInputs[$key] is not an InputObject");
        }
    }

}

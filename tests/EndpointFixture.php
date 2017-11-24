<?php

declare(strict_types=1);

namespace Firehed\API;

use Throwable;
use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Objects\InputObject;
use PHPUnit_Framework_MockObject_Generator as Generator;
use PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce as AtLeastOnce;
use PHPUnit_Framework_MockObject_Stub_Return as ReturnValue;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class EndpointFixture implements Interfaces\EndpointInterface
{

    const STATUS_ERROR = 999;

    public function authenticate(Request $request): Interfaces\EndpointInterface
    {
        return $this;
    }

    public function getUri(): string
    {
        return '/user/(?P<id>[1-9]\d*)';
    }

    public function getRequiredInputs(): array
    {
        return [
            'id' => new WholeNumber(),
        ];
    }

    public function getOptionalInputs(): array
    {
        return [
            'shortstring' => (new Text())->setMax(5),
        ];
    }

    public function getMethod(): Enums\HTTPMethod
    {
        return Enums\HTTPMethod::GET();
    }

    public function execute(SafeInput $input): Response
    {
        // Use PHPUnit mocks outside of the TestCase... the DSL isn't quite as
        // pretty here :)
        $mockgen = new Generator();
        $mock = $mockgen->getMock('Psr\Http\Message\ResponseInterface');
        $mock->expects(new AtLeastOnce())
            ->method('getStatusCode')
            ->will(new ReturnValue(200));
        $mock->expects(new AtLeastOnce())
            ->method('getBody')
            ->will(new ReturnValue(json_encode($input->asArray())));
        return $mock;
    }

    public function handleException(Throwable $e): Response
    {
        $mock = (new Generator())
            ->getMock('Psr\Http\Message\ResponseInterface');
        $mock->method('getStatusCode')
            ->will(new ReturnValue(self::STATUS_ERROR)); // Artificial test value
        return $mock;
    }
}

class WholeNumber extends InputObject
{
    public function validate($value): bool
    {
        return ((int)$value) == $value;
    }

    public function evaluate()
    {
        return $this->getValue() + 0;
    }
}
class Text extends InputObject
{

    private $max = \PHP_INT_MAX;

    public function setMax(int $max): self
    {
        $this->max = $max;
        return $this;
    }

    public function validate($value): bool
    {
        return strlen($value) <= $this->max;
    }
}

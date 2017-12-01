<?php

declare(strict_types=1);

namespace Firehed\API;

use Throwable;
use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Objects\InputObject;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\Matcher\InvokedAtLeastOnce;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
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
            'id' => new class extends InputObject {
                public function validate($value): bool
                {
                    return ((int)$value) == $value;
                }

                public function evaluate()
                {
                    return $this->getValue() + 0;
                }
            },
        ];
    }

    public function getOptionalInputs(): array
    {
        return [
            'shortstring' => new class extends InputObject {
                public function validate($value): bool
                {
                    return strlen($value) <= 5;
                }
            }
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
        $mock = $mockgen->getMock(Response::class);
        $mock->expects(new InvokedAtLeastOnce())
            ->method('getStatusCode')
            ->will(new ReturnStub(200));
        $mock->expects(new InvokedAtLeastOnce())
            ->method('getBody')
            ->will(new ReturnStub(json_encode($input->asArray())));
        return $mock;
    }

    public function handleException(Throwable $e): Response
    {
        $mock = (new Generator())
            ->getMock(Response::class);
        $mock->method('getStatusCode')
            ->will(new ReturnStub(self::STATUS_ERROR)); // Artificial test value
        return $mock;
    }
}

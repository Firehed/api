<?php

declare(strict_types=1);

namespace Firehed\API;

use Throwable;
use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Objects\InputObject;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastOnce;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class EndpointFixture implements Interfaces\EndpointInterface
{
    use Traits\Request\Get;

    const STATUS_ERROR = 999;

    public function getUri(): string
    {
        return '/user/(?P<id>[1-9]\d*)';
    }

    public function getRequiredInputs(): array
    {
        return [
            'id' => new class extends InputObject {
                /** @param mixed $value */
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
                /** @param mixed $value */
                public function validate($value): bool
                {
                    return strlen($value) <= 5;
                }
            }
        ];
    }

    public function execute(SafeInput $input): Response
    {
        // Use PHPUnit mocks outside of the TestCase... the DSL isn't quite as
        // pretty here :)
        $mockgen = new Generator();
        /** @var Response | \PHPUnit\Framework\MockObject\MockObject */
        $mock = $mockgen->getMock(Response::class);
        $mock->expects(new InvokedAtLeastOnce())
            ->method('getStatusCode')
            ->will(new ReturnStub(200));
        $mock->expects(new InvokedAtLeastOnce())
            ->method('getBody')
            ->will(new ReturnStub(json_encode($input->asArray())));
        return $mock;
    }
}

<?php

declare(strict_types=1);

namespace Firehed\API;

use Throwable;
use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Objects\InputObject;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class EndpointFixture implements Interfaces\EndpointInterface
{

    const STATUS_ERROR = 598;

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
        return new \Zend\Diactoros\Response\JsonResponse(
            $input->asArray(),
            200
        );
    }

    public function handleException(Throwable $e): Response
    {
        $code = $e->getCode();
        if ($code < 200 || $code > 599) {
            $code = self::STATUS_ERROR; // Artificial test value
        }
        return new \Zend\Diactoros\Response\TextResponse(
            (string) $e,
            $code
        );
    }
}

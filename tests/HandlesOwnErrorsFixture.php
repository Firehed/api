<?php

declare(strict_types=1);

namespace Firehed\API;

use PHPUnit\Framework\MockObject\Generator;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class HandlesOwnErrorsFixture implements Interfaces\HandlesOwnErrorsInterface
{
    public function handleException(Throwable $e): ResponseInterface
    {
        /** @var ResponseInterface */
        $mock = (new Generator())
            ->getMock(ResponseInterface::class);
        return $mock;
    }
}

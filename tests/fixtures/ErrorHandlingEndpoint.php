<?php

declare(strict_types=1);

namespace Firehed\API\fixtures;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Firehed\API\Traits\Request\Get;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ErrorHandlingEndpoint implements EndpointInterface, HandlesOwnErrorsInterface
{
    use Get;

    /**
     * @var callable
     */
    private $execute;

    /**
     * @var callable
     */
    private $handleException;

    public function __construct(callable $execute, callable $handleException)
    {
        $this->execute = $execute;
        $this->handleException = $handleException;
    }

    public function getRequiredInputs(): array
    {
        return [];
    }

    public function getOptionalInputs(): array
    {
        return [];
    }

    public function getUri(): string
    {
        return '';
    }

    public function execute($input): ResponseInterface
    {
        return ($this->execute)($input);
    }

    public function handleException(Throwable $e): ResponseInterface
    {
        return ($this->handleException)($e);
    }
}

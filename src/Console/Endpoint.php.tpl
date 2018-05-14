<?php
declare(strict_types=1);

namespace %s;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Traits\Authentication;
use Firehed\API\Traits\Input;
use Firehed\API\Traits\Request;
use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class %s implements EndpointInterface
{
    // use Authentication\None;
    // use Input\NoOptional;
    // use Input\NoRequired;
    // use Request\Get;

    public function getUri(): string
    {
        return '/%s';
    }

    public function getRequiredInputs(): array
    {
        return [];
    }

    public function getOptionalInputs(): array
    {
        return [];
    }

    public function execute(SafeInput $input): ResponseInterface
    {
        // Implement this
    }

    public function handleException(Throwable $t): ResponseInterface
    {
        // Implememt this
    }
}

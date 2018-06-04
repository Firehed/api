<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

interface ExceptionHandlerInterface
{

    public function register(string $exceptionClass, callable $handler): self;

}

<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationProviderInterface
{
    public function authenticate(ServerRequestInterface $request): AuthenticationContainerInterface;
}

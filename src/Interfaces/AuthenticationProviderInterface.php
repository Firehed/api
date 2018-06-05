<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationProviderInterface
{
    /**
     * Upon successful authentication, the provider MUST return an
     * AuthenticationContainerInterface. It is RECOMMENDED that implementations
     * make authn data available with fully-qualified class names when
     * possible.
     *
     * If authentication fails, the provider MUST throw
     * a Firehed\API\Exceptions\AuthenticationException.
     */
    public function authenticate(ServerRequestInterface $request): AuthenticationContainerInterface;
}

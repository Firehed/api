<?php
declare(strict_types=1);

namespace Firehed\API\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

interface ProviderInterface
{
    /**
     * Upon successful authentication, the provider MUST return a
     * ContainerInterface. It is RECOMMENDED that implementations make authn
     * data available with fully-qualified class names when possible.
     *
     * If authentication fails, the provider MUST throw
     * a Firehed\API\Authentication\Exception.
     */
    public function authenticate(ServerRequestInterface $request): ContainerInterface;
}

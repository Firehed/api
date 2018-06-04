<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
// These need to be organized or possibly split into their own repo

interface BearerTokenProcessorInterface
{
    public function process(string $token): AuthenticationContainerInterface;
}

class BearerTokenAuthentication implements AuthenticationProviderInterface
{
    public function __construct(BearerTokenProcessorInterface $consumer)
    {
        $this->consumer = $consumer;
    }

    public function authenticate(ServerRequestInterface $request): AuthenticationContainerInterface
    {
        // error checking...
        list($_, $token) = explode(' ', $request->getHeaderLine('Authorization'), 2);

        return $this->consumer->process($token);
    }
}

abstract class BasicAuthAuthentiation implements AuthenticationProviderInterface
{
    public function authenticate(ServerRequestInterface $request): AuthenticationContainerInterface
    {
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        // ...
    }
}

class ChainAuthenticationProvider implements AuthenticationProviderInterface
{
    private $providers;

    public function addProvider(AuthenticationProviderInterface $provider): ChainAuthProvider
    {
        $this->providers[] = $provider;
        return $this;
    }
    public function authenticate(ServerRequestInterface $request): AuthenticationContainerInterface
    {
        foreach ($this->providers as $provider) {
            $auth = $provider->authenticate($request);
            if ($auth->isAuthenticated()) {
                return $auth;
            }
        }
        return new class implements AuthenticationInterface {
            public function isAuthenticated(): bool
            {
                return false;
            }
        };
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Firehed\API\Authentication;
use Firehed\API\Authorization;
use Psr\Http\Message\ServerRequestInterface;
// These need to be organized or possibly split into their own repo

interface BearerTokenProcessorInterface
{
    public function process(string $token): Authentication\ContainerInterface;
}

class BearerTokenAuthentication implements Authentication\ProviderInterface
{
    public function __construct(BearerTokenProcessorInterface $consumer)
    {
        $this->consumer = $consumer;
    }

    public function authenticate(ServerRequestInterface $request): Authentication\ContainerInterface
    {
        // error checking...
        list($_, $token) = explode(' ', $request->getHeaderLine('Authorization'), 2);

        return $this->consumer->process($token);
    }
}

abstract class BasicAuthAuthentiation implements Authentication\ProviderInterface
{
    public function authenticate(ServerRequestInterface $request): Authentication\ContainerInterface
    {
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        // ...
    }
}

class ChainAuthenticationProvider implements Authentication\ProviderInterface
{
    private $providers;

    public function addProvider(Authentication\ProviderInterface $provider): ChainAuthenticationProvider
    {
        $this->providers[] = $provider;
        return $this;
    }
    public function authenticate(ServerRequestInterface $request): Authentication\ContainerInterface
    {
        foreach ($this->providers as $provider) {
            try {
                return $provider->authenticate($request);
            } catch (Authentication\Exception $e) {
            }
        }
        // Maybe? This could be some wacky exception(previous) chain rather
        // than just the last one.
        throw $e;
    }
}

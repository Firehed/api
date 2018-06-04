<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

interface AuthorizationProviderInterface
{
    public function authorize(
        AuthenticatedEndpointInterface $endpoint,
        AuthenticationContainerInterface $auth
    ): bool;
}

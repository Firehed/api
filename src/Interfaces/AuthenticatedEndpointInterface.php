<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

interface AuthenticatedEndpointInterface extends EndpointInterface
{
    public function setAuthentication(AuthenticationContainerInterface $auth);
}

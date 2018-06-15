<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Psr\Container\ContainerInterface;

interface AuthenticatedEndpointInterface extends EndpointInterface
{
    public function setAuthentication(ContainerInterface $auth);
}

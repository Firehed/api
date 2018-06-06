<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Firehed\API\Authentication\ContainerInterface;

interface AuthenticatedEndpointInterface extends EndpointInterface
{
    public function setAuthentication(ContainerInterface $auth);
}

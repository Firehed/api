<?php
declare(strict_types=1);

namespace Firehed\API\Authorization;

use Firehed\API\Authentication;
use Firehed\API\Interfaces\AuthenticatedEndpointInterface;

interface ProviderInterface
{
    /**
     * Authorize the endpoint using the authentication data provided in the
     * container. Implementations MUST throw an Exception upon failure, and
     * MUST return an Ok upon success.
     *
     * @throws Exception
     */
    public function authorize(
        AuthenticatedEndpointInterface $endpoint,
        Authentication\ContainerInterface $auth
    ): Ok;
}

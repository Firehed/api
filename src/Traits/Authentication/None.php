<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Authentication;

use Firehed\API\Interfaces\EndpointInterface;
use Psr\Http\Message\RequestInterface;

trait None
{

    public function authenticate(RequestInterface $request): EndpointInterface
    {
        return $this;
    }

}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Authentication;

use Firehed\API\Interfaces\AuthenticationContainerInterface;

trait Holder
{
    private $auth;

    public function setAuthentication(AuthenticationContainerInterface $auth)
    {
        $this->auth = $auth;
    }
}

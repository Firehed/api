<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

use Firehed\API\Enums\HTTPMethod;

trait Put
{
    public function getMethod(): HTTPMethod
    {
        return HTTPMethod::PUT();
    }

}

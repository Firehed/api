<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

use Firehed\API\Enums\HTTPMethod;

trait Get
{
    public function getMethod(): string
    {
        return HTTPMethod::GET;
    }
}

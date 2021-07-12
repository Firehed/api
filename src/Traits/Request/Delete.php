<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

use Firehed\API\Enums\HTTPMethod;

trait Delete
{
    public function getMethod(): string
    {
        return HTTPMethod::DELETE;
    }
}

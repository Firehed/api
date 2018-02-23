<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

use Firehed\API\Enums\HTTPMethod;

trait Options
{

    public function getMethod(): HTTPMethod
    {
        return HTTPMethod::OPTIONS();
    }
}

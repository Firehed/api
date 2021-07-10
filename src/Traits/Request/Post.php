<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Request;

use Firehed\API\Enums\HTTPMethod;

trait Post
{
    public function getMethod()
    {
        return HTTPMethod::POST;
    }
}

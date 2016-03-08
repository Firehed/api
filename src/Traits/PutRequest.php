<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Enums\HTTPMethod;

trait PutRequest
{
    public function getMethod(): HTTPMethod
    {
        return HTTPMethod::PUT();
    }

}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Enums\HTTPMethod;

trait GetRequest
{

    public function getMethod(): HTTPMethod
    {
        return HTTPMethod::GET();
    }

}

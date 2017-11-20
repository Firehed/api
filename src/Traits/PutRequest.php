<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Enums\HTTPMethod;

trait PutRequest
{

    public function getMethod(): HTTPMethod
    {
        trigger_error(
            'Moved to Firehed\API\Traits\Request\Put',
            \E_USER_DEPRECATED
        );
        return HTTPMethod::PUT();
    }
}

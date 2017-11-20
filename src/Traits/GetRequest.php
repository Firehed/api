<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Enums\HTTPMethod;

trait GetRequest
{

    public function getMethod(): HTTPMethod
    {
        trigger_error(
            'Moved to Firehed\API\Traits\Request\Get',
            \E_USER_DEPRECATED
        );
        return HTTPMethod::GET();
    }
}

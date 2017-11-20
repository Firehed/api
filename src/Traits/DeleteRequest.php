<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Firehed\API\Enums\HTTPMethod;

trait DeleteRequest
{

    public function getMethod(): HTTPMethod
    {
        trigger_error(
            'Moved to Firehed\API\Traits\Request\Delete',
            \E_USER_DEPRECATED
        );
        return HTTPMethod::DELETE();
    }
}

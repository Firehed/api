<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

trait NoOptionalInputs
{

    public function getOptionalInputs(): array
    {
        trigger_error(
            'Moved to Firehed\API\Traits\Input\NoOptional',
            \E_USER_DEPRECATED
        );
        return [];
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

trait NoRequiredInputs
{

    public function getRequiredInputs(): array
    {
        trigger_error('Moved to Firehed\API\Traits\Input\NoRequired',
            \E_USER_DEPRECATED);
        return [];
    }

}

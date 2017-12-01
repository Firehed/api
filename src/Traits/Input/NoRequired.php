<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

trait NoRequired
{

    public function getRequiredInputs(): array
    {
        return [];
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

trait NoOptional
{

    public function getOptionalInputs(): array
    {
        return [];
    }

}

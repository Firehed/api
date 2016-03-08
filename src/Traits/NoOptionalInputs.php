<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

trait NoOptionalInputs
{

    public function getOptionalInputs(): array
    {
        return [];
    }

}

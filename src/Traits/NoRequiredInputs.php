<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

trait NoRequiredInputs
{

    public function getRequiredInputs(): array
    {
        return [];
    }

}

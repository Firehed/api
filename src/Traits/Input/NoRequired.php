<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

trait NoRequired
{

    /** @return array<string, \Firehed\Input\Objects\InputObject> */
    public function getRequiredInputs(): array
    {
        return [];
    }
}

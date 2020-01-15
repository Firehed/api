<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Input;

trait NoOptional
{

    /**
     * @return array<string, \Firehed\Input\Objects\InputObject>
     */
    public function getOptionalInputs(): array
    {
        return [];
    }
}

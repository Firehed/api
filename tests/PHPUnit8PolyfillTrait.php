<?php

declare(strict_types=1);

namespace Firehed\API;

use PHPUnit\Framework\Error;

trait PHPUnit8PolyfillTrait
{
    public function expectDeprecation(): void
    {
        $this->expectException(Error\Deprecated::class);
    }
}

<?php

declare(strict_types=1);

namespace Firehed\API;

use PHPUnit\Framework\Error;

trait PHPUnit8PolyfillTrait
{
    public static function expectDeprecation(): void
    {
        self::expectException(Error\Deprecated::class);
    }
}

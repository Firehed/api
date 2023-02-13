<?php

declare(strict_types=1);

namespace Firehed\API;

use PHPUnit\Runner\Version;

use function version_compare;

if (version_compare(Version::id(), '10.0.0', '>=')) {
    trait PHPUnitPolyfillTrait
    {
    }
} elseif (version_compare(Version::id(), '9.0.0', '>=')) {
    trait PHPUnitPolyfillTrait
    {
    }
} elseif (version_compare(Version::id(), '8.0.0', '>=')) {
    trait PHPUnitPolyfillTrait
    {
    }
} elseif (version_compare(Version::id(), '7.0.0', '>=')) {
    trait PHPUnitPolyfillTrait
    {
        use PHPUnit8PolyfillTrait;
    }
}

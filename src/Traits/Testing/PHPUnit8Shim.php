<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Testing;

use PHPUnit\Runner\Version;

/**
 * phpcs:disable
 *
 * Implement some horrible hacks to allow PHP7.0 users to use assertIsString
 * which natively has a void return type.
 *
 * @internal
 */
if (class_exists(Version::class) && version_compare(Version::id(), '10.0.0', '>=')) {
    trait PHPUnit8Shim
    {
        // Intentionally empty
    }
} elseif (version_compare(PHP_VERSION, '7.1.0', '>=')) {
    trait PHPUnit8Shim
    {
        use PHPUnit8ShimPHPGTE71;
    }
} else {
    trait PHPUnit8Shim
    {
        use PHPUnit8ShimPHPLT71;
    }
}

<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Testing;

use PHPUnit\Framework\TestCase;

/**
 * PHP 7.0-compatible shim (stripped return types)
 * Simply wraps assertInternalType since PHPUnit 7.5 (where the new versions
 * became available) requires 7.1 or later.
 */
trait PHPUnit8ShimPHPLT71
{
    public static function assertIsArray($actual, string $message = '')
    {
        TestCase::assertInternalType('array', $actual, $message);
    }

    public static function assertIsBool($actual, string $message = '')
    {
        TestCase::assertInternalType('bool', $actual, $message);
    }

    public static function assertIsString($actual, string $message = '')
    {
        TestCase::assertInternalType('string', $actual, $message);
    }
}

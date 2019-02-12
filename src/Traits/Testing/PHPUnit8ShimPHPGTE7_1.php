<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Testing;

use PHPUnit\Framework\TestCase;

trait PHPUnit8Shim
{
    public static function assertIsArray($actual, string $message = ''): void
    {
        if (method_exists(TestCase::class, 'assertIsArray')) {
            TestCase::assertIsArray($actual, $message);
        } else {
            TestCase::assertInternalType('array', $actual, $message);
        }
    }

    public static function assertIsBool($actual, string $message = ''): void
    {
        if (method_exists(TestCase::class, 'assertIsBool')) {
            TestCase::assertIsBool($actual, $message);
        } else {
            TestCase::assertInternalType('bool', $actual, $message);
        }
    }

    public static function assertIsString($actual, string $message = ''): void
    {
        if (method_exists(TestCase::class, 'assertIsString')) {
            TestCase::assertIsString($actual, $message);
        } else {
            TestCase::assertInternalType('string', $actual, $message);
        }
    }
}

<?php
declare(strict_types=1);

/**
 * Implement some horrible hacks to allow PHP7.0 users to use assertIsString
 * which natively has a void return type.
 * @internal
 */
if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
    include 'PHPUnit8ShimPHPGTE7_1.php';
} else {
    include 'PHPUnit8ShimPHPLT7_1.php';
}

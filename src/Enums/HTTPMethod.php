<?php

declare(strict_types=1);

namespace Firehed\API\Enums;

use function class_alias;
use function version_compare;

use const PHP_VERSION;

if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
    class_alias(HTTPMethodGTE81::class, HTTPMethod::class);
} else {
    class_alias(HTTPMethodLTE80::class, HTTPMethod::class);
}

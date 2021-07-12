<?php

declare(strict_types=1);

namespace Firehed\API\Enums;

/**
 * Direct use of this class (via these static methods) is deprecated. Instead,
 * implementations should rely on the Request traits.
 *
 * This will be converted to a native Enum in PHP 8.1.
 *
 * @internal
 */
interface HTTPMethodLTE80
{
    // Other methods exist, but these are the only relevant ones for RESTful
    // APIs
    const GET = 'GET';
    const PATCH = 'PATCH';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';
}

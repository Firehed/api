<?php

declare(strict_types=1);

namespace Firehed\API\Enums;

/**
 * @internal
 */
enum HTTPMethodGTE81: string
{
    case GET = 'GET';
    case PATCH = 'PATCH';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
}

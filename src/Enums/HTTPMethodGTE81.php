<?php

declare(strict_types=1);

namespace Firehed\API\Enums;

enum HTTPMethodGTE81
{
    case GET = 'GET';
    case PATCH = 'PATCH';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
}

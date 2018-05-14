<?php

declare(strict_types=1);

namespace Firehed\API\Enums;

use Firehed\Common\Enum;

/**
 * @method static HTTPMethod DELETE()
 * @method static HTTPMethod GET()
 * @method static HTTPMethod OPTIONS()
 * @method static HTTPMethod POST()
 * @method static HTTPMethod PUT()
 */
class HTTPMethod extends Enum
{

    // Other methods exist, but these are the only relevant ones for RESTful
    // APIs
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';

    public function __toString()
    {
        return $this->getValue();
    }
}

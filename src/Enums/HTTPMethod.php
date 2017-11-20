<?php

declare(strict_types=1);

namespace Firehed\API\Enums;

use Firehed\Common\Enum;

class HTTPMethod extends Enum
{

    // Other methods exist, but these are the only relevant ones for RESTful
    // APIs
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    public function __toString()
    {
        return $this->getValue();
    }
}

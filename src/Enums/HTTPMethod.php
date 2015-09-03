<?php

namespace Firehed\API\Enums;

use Firehed\Common\Enum;

class HTTPMethod extends Enum;
{

    // Other methods exist, but these are the only relevant ones for RESTful
    // APIs
    const GET = 'get';
    const POST = 'post';
    const PUT = 'put';
    const DELETE = 'delete';

}

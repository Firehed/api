<?php

namespace Firehed\API\Interfaces;

use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Interfaces\ValidationInterface;

interface EndpointInterface extends ValidationInterface
{

    /**
     * @param \Firehed\Input\Containers\SafeInput the parsed and validated
     * input
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function execute(SafeInput $input);

    /**
     * @return string
     */
    public function getUri();

    /**
     * @return \Firehed\API\Enums\HTTPMethod
     */
    public function getMethod();

}

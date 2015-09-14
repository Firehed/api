<?php

namespace Firehed\API;

use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects\Text;
use Firehed\InputObjects\WholeNumber;
use Zend\Diactoros\Response\JsonResponse;

class EndpointFixture implements Interfaces\EndpointInterface
{

    public function getUri()
    {
        return '/user/(?P<id>[1-9]\d*)';
    }

    public function getRequiredInputs()
    {
        return [
            'id' => new WholeNumber(),
        ];
    }

    public function getOptionalInputs()
    {
        return [
            'shortstring' => (new Text())->setMax(5),
        ];
    }

    public function getMethod()
    {
        return Enums\HTTPMethod::GET();
    }

    public function execute(SafeInput $input)
    {
        return new JsonResponse($input->asArray());
    }


}

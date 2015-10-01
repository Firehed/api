<?php

namespace Firehed\API;

use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects\Text;
use Firehed\InputObjects\WholeNumber;
use PHPUnit_Framework_Mockobject_Generator as Generator;
use PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce as AtLeastOnce;
use PHPUnit_Framework_MockObject_Stub_Return as ReturnValue;
use Psr\Http\Message\RequestInterface as Request;

class EndpointFixture implements Interfaces\EndpointInterface
{

    public function authenticate(Request $request)
    {
        return $this;
    }

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
        // Use PHPUnit mocks outside of the TestCase... the DSL isn't quite as
        // pretty here :)
        $mockgen = new Generator();
        $mock = $mockgen->getMock('Psr\Http\Message\ResponseInterface');
        $mock->expects(new AtLeastOnce())
            ->method('getStatusCode')
            ->will(new ReturnValue(200));
        $mock->expects(new AtLeastOnce())
            ->method('getBody')
            ->will(new ReturnValue(json_encode($input->asArray())));
        return $mock;
    }

}

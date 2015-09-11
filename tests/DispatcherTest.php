<?php

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass Firehed\API\Dispatcher
 * @covers ::<protected>
 * @covers ::<private>
 */
class DispatcherTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $this->assertInstanceOf('Firehed\API\Dispatcher',
            new Dispatcher());
    }


    public function testNoRouteMatchReturns404()
    {
        $this->i();
    }

    /** @covers ::dispatch */
    public function testDispatchReturns500WhenMissingData()
    {
        $d = new Dispatcher();
        $ret = $d->dispatch();
        $this->checkResponse($ret, 500);
    }


    /** @covers ::setEndpointList */
    public function testSetEndpointListReturnsSelf()
    {
        $d = new Dispatcher();
        $this->assertSame($d,
            $d->setEndpointList('list'),
            'setEndpointList did not return $this');
    }

    /** @covers ::setParserList */
    public function testSetParserListReturnsSelf()
    {
        $d = new Dispatcher();
        $this->assertSame($d,
            $d->setParserList('list'),
            'setParserList did not return $this');
    }

    /** @covers ::setRequest */
    public function testSetRequestReturnsSelf()
    {
        $d = new Dispatcher();
        $req = $this->getMock('Psr\Http\Message\RequestInterface');
        $this->assertSame($d,
            $d->setRequest($req),
            'setRequest did not return $this');
    }





    function i() {
        $this->markTestIncomplete();
    }

    /**
     * @param ResponseInterface response to test
     * @param int HTTP status code to check for
     */
    private function checkResponse(ResponseInterface $response, $expected_code)
    {
        $this->assertSame($expected_code,
            $response->getStatusCode(),
            'Incorrect status code in response');
    }

}

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

    // ----(Setters)-----------------------------------------------------------

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

    // ----(Success case)-------------------------------------------------------

    /**
     * Test successful all-the-way-through controller execution, including both
     * URL-provided data (regex captures) and POST body
     *
     * @covers ::dispatch
     */
    public function testDataReachesEndpoint()
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        $req->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('shortstring=aBcD'));
        $req->expects($this->any())
            ->method('getHeader')
            ->with('Content-type')
            ->will($this->returnValue(['application/x-www-form-urlencoded']));

        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        $this->checkResponse($response, 200);
        $data = json_decode($response->getBody(), true);
        $this->assertSame([
                'id' => 5,
                'shortstring' => 'aBcD',
            ],
            $data,
            'The data did not reach the endpoint');
    }

    // ----(Error cases)--------------------------------------------------------

    /** @covers ::dispatch */
    public function testDispatchReturns500WhenMissingData()
    {
        $d = new Dispatcher();
        $ret = $d->dispatch();
        $this->checkResponse($ret, 500);
    }

    /** @covers ::dispatch */
    public function testNoRouteMatchReturns404()
    {
        $req = $this->getMockRequestWithUriPath('/');

        $ret = (new Dispatcher())
            ->setRequest($req)
            ->setEndpointList([]) // No routes
            ->setParserList([])
            ->dispatch();
        $this->checkResponse($ret, 404);
    }

    /** @covers ::dispatch */
    public function testFailedInputValidationReturns400()
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath('/user/5', 'POSt');
        $req->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('shortstring=thisistoolong'));
        $req->expects($this->any())
            ->method('getHeader')
            ->with('Content-type')
            ->will($this->returnValue(['application/x-www-form-urlencoded']));

        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        $this->checkResponse($response, 400);
    }

    /** @covers ::dispatch */
    public function testUnsupportedContentTypeReturns400()
    {
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        $req->expects($this->any())
            ->method('getHeader')
            ->with('Content-type')
            ->will($this->returnValue(['application/x-test-failure']));
         $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        $this->checkResponse($response, 400);
    }

    // ----(Helper methods)----------------------------------------------------

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

    /**
     * Convenience method to get a mock PSR-7 Request that will itself support
     * returning a mock PSR-7 URI with the provided path, and the HTTP method
     * if provided
     *
     * @param string path component of URI
     * @param ?string optional HTTP method
     * @return \Psr\Http\Message\UriInterface
     */
    private function getMockRequestWithUriPath($uri, $method = null)
    {
        $mock_uri = $this->getMock('Psr\Http\Message\UriInterface');
        $mock_uri->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($uri));

        $req = $this->getMock('Psr\Http\Message\RequestInterface');

        $req->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($mock_uri));

        if ($method) {
            $req->expects($this->any())
                ->method('getMethod')
                ->will($this->returnValue($method));
        }
        return $req;
    }

    private function getEndpointListForFixture()
    {
        return [
            'POST' => [
                '/user/(?P<id>[1-9]\d*)' => __NAMESPACE__.'\EndpointFixture'
            ],
        ];
    }

    private function getDefaultParserList()
    {
        // This could also be dynamically built
        return dirname(__DIR__).'/vendor/firehed/input/src/Parsers/__parser_list__.json';
    }

}

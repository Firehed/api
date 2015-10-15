<?php

namespace Firehed\API;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Firehed\API\Interfaces\EndpointInterface as Endpoint;

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

    /** @covers ::setContainer */
    public function testSetContainerReturnsSelf()
    {
        $d = new Dispatcher();
        $this->assertSame($d,
            $d->setContainer([]),
            'setContainer did not return $this');
    }

    /** @covers ::setContainer */
    public function testSetContainerAcceptsArrayAccess()
    {
        $d = new Dispatcher();
        $this->assertSame($d,
            $d->setContainer($this->getMock('ArrayAccess')),
            'setContainer did not return $this');
    }

    /**
     * @covers ::setContainer
     * @dataProvider nonArrays
     * @expectedException UnexpectedValueException
     */
    public function testSetContainerRejectsNonArrayLike($non_array)
    {
        $d = new Dispatcher();
        $d->setContainer($non_array);
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

    // ----(Success case)-------------------------------------------------------

    /**
     * Test successful all-the-way-through controller execution, including both
     * URL-provided data (regex captures) and POST body.
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

    /**
     * Test successful all-the-way-through controller execution, focusing on
     * accessing $_GET/querystring data
     *
     * @covers ::dispatch
     */
    public function testQueryStringDataReachesEndpoint()
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath('/user/5', 'GET', ['shortstring' => 'aBcD']);
        $req->method('getBody')
            ->will($this->returnValue('shortstring=aBcD'));

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

    /**
     * Default behavior is to directly use the class found in the endpoint
     * list. If a value exists in the DI Container (well, really a service
     * locator now) at the key of that class name, it should be preferred. This
     * allows configuring the class without having to do crazy magic in the
     * dispatcher.
     *
     * @covers ::dispatch
     */
    public function testContainerClassIsPrioritized()
    {
        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue(
                $this->getMock('Psr\Http\Message\ResponseInterface')));
        $this->executeMockRequestOnEndpoint($endpoint);
    }

    // ----(Error cases)--------------------------------------------------------

    /**
     * @covers ::dispatch
     * @expectedException BadMethodCallException
     * @expectedExceptionCode 500
     */
    public function testDispatchThrowsWhenMissingData()
    {
        $d = new Dispatcher();
        $ret = $d->dispatch();
    }

    /**
     * @covers ::dispatch
     * @expectedException OutOfBoundsException
     * @expectedExceptionCode 404
     */
    public function testNoRouteMatchReturns404()
    {
        $req = $this->getMockRequestWithUriPath('/');

        $ret = (new Dispatcher())
            ->setRequest($req)
            ->setEndpointList([]) // No routes
            ->setParserList([])
            ->dispatch();
    }

    /** @covers ::dispatch */
    public function testFailedInputValidationReachesErrorHandler()
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
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
        $this->assertSame(EndpointFixture::STATUS_ERROR,
            $response->getStatusCode());
    }

    /** @covers ::dispatch */
    public function testUnsupportedContentTypeReachesErrorHandler()
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
        $this->assertSame(EndpointFixture::STATUS_ERROR,
            $response->getStatusCode());
    }

    /** @covers ::dispatch */
    public function testFailedAuthenticationReachesErrorHandler()
    {
        $e = new Exception('This should reach the error handler');
        $endpoint = $this->getMockEndpoint();
        $endpoint->method('authenticate')
            ->will($this->throwException($e));
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($e);
        $this->executeMockRequestOnEndpoint($endpoint);

    }

    /** @covers ::dispatch */
    public function testFailedEndpointExecutionReachesErrorHandler()
    {
        $e = new Exception('This should reach the error handler');
        $endpoint = $this->getMockEndpoint();
        $endpoint->method('execute')
            ->will($this->throwException($e));
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($e);
        $this->executeMockRequestOnEndpoint($endpoint);
    }


    /** @covers ::dispatch */
    public function testScalarResponseFromEndpointReachesErrorHandler()
    {
        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue(false)); // Trigger a bad return value
        $endpoint->expects($this->once())
            ->method('handleException');
        $this->executeMockRequestOnEndpoint($endpoint);
    }

    /** @covers ::dispatch */
    public function testInvalidTypeResponseFromEndpointReachesErrorHandler()
    {
        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue(new \DateTime())); // Trigger a bad return value
        $endpoint->expects($this->once())
            ->method('handleException');
        $this->executeMockRequestOnEndpoint($endpoint);
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
     * @param ?array optional raw, unescaped query string data
     * @return \Psr\Http\Message\UriInterface
     */
    private function getMockRequestWithUriPath($uri, $method = null, $query_data = [])
    {
        $mock_uri = $this->getMock('Psr\Http\Message\UriInterface');
        $mock_uri->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($uri));
        $mock_uri->method('getQuery')
            ->will($this->returnValue(http_build_query($query_data)));

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

    /**
     * Convenience method for mocking an endpoint. The mock has no required or
     * optional inputs.
     *
     * @return Endpoint
     */
    private function getMockEndpoint()
    {
        $endpoint = $this->getMock('Firehed\API\Interfaces\EndpointInterface');
        $endpoint->method('getRequiredInputs')
            ->will($this->returnValue([]));
        $endpoint->method('getOptionalInputs')
            ->will($this->returnValue([]));
        $endpoint->method('handleException')
            ->will($this->returnValue($this->getMock('Psr\Http\Message\ResponseInterface')));
        return $endpoint;
    }

    /**
     * Run the endpointwith an empty request
     *
     * @param Endpoint the endpoint to test
     * @return ResponseInterface the endpoint response
     */
    private function executeMockRequestOnEndpoint(Endpoint $endpoint)
    {
        $req = $this->getMockRequestWithUriPath('/container', 'GET');
        $list = [
            'GET' => [
                '/container' => 'ClassThatDoesNotExist',
            ],
        ];
        $response = (new Dispatcher())
            ->setContainer(['ClassThatDoesNotExist' => $endpoint])
            ->setEndpointList($list)
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        return $response;
    }

    private function getEndpointListForFixture()
    {
        return [
            'GET' => [
                '/user/(?P<id>[1-9]\d*)' => __NAMESPACE__.'\EndpointFixture'
            ],
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

    // ----(DataProviders)-----------------------------------------------------

    public function nonArrays()
    {
        return [
            [''],
            [false],
            [null],
            [new \StdClass()],
        ];
    }

}

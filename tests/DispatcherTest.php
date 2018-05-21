<?php

declare(strict_types=1);

namespace Firehed\API;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Firehed\API\Interfaces\EndpointInterface;

/**
 * @coversDefaultClass Firehed\API\Dispatcher
 * @covers ::<protected>
 * @covers ::<private>
 */
class DispatcherTest extends \PHPUnit\Framework\TestCase
{

    public function testConstruct()
    {
        $this->assertInstanceOf(
            'Firehed\API\Dispatcher',
            new Dispatcher()
        );
    }

    // ----(Setters)-----------------------------------------------------------

    /** @covers ::setContainer */
    public function testSetContainerReturnsSelf()
    {
        $d = new Dispatcher();
        $container = $this->getMockContainer([]);
        $this->assertSame(
            $d,
            $d->setContainer($container),
            'setContainer did not return $this'
        );
    }

    /** @covers ::setEndpointList */
    public function testSetEndpointListReturnsSelf()
    {
        $d = new Dispatcher();
        $this->assertSame(
            $d,
            $d->setEndpointList('list'),
            'setEndpointList did not return $this'
        );
    }

    /** @covers ::setParserList */
    public function testSetParserListReturnsSelf()
    {
        $d = new Dispatcher();
        $this->assertSame(
            $d,
            $d->setParserList('list'),
            'setParserList did not return $this'
        );
    }

    /** @covers ::setRequest */
    public function testSetRequestReturnsSelf()
    {
        $d = new Dispatcher();
        $req = $this->createMock(RequestInterface::class);
        $this->assertSame(
            $d,
            $d->setRequest($req),
            'setRequest did not return $this'
        );
    }

    /** @covers ::addResponseMiddleware */
    public function testAddResponseMiddlewareReturnsSelf()
    {
        $d = new Dispatcher();
        $this->assertSame($d, $d->addResponseMiddleware(function ($r, $n) {
            return $n($r);
        }), 'addResponseMiddleware did not return $this');
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
        $this->assertSame(
            [
                'id' => 5,
                'shortstring' => 'aBcD',
            ],
            $data,
            'The data did not reach the endpoint'
        );
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
        $req = $this->getMockRequestWithUriPath(
            '/user/5',
            'GET',
            ['shortstring' => 'aBcD']
        );
        $req->method('getBody')
            ->will($this->returnValue('shortstring=aBcD'));

        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        $this->checkResponse($response, 200);
        $data = json_decode($response->getBody(), true);
        $this->assertSame(
            [
                'id' => 5,
                'shortstring' => 'aBcD',
            ],
            $data,
            'The data did not reach the endpoint'
        );
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
                $this->createMock(ResponseInterface::class)
            ));
        $this->executeMockRequestOnEndpoint($endpoint);
    }

    // Value to be set by a callback if it is run as desired
    private $response_hits = 0;
    public function testAllResponseMiddlewaresAreReachable()
    {
        $endpoint = $this->getMockEndpoint();
        $req = $this->getMockRequestWithUriPath('/cb', 'GET');
        $list = [
            'GET' => [
                '/cb' => 'CBClass',
            ],
        ];
        (new Dispatcher())
            ->setContainer($this->getMockContainer(['CBClass' => $endpoint]))
            ->setEndpointList($list)
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->addResponseMiddleware(function ($response, $next) {
                $this->response_hits++;
                return $next($response);
            })
            ->addResponseMiddleware(function ($response, $next) {
                $this->response_hits++;
                return $next($response);
            })
            ->dispatch();
        $this->assertSame(
            2,
            $this->response_hits,
            'Not all response callbacks were fired'
        );
    }

    public function testAllResponseMiddlewaresAreFIFO()
    {
        $endpoint = $this->getMockEndpoint();
        $req = $this->getMockRequestWithUriPath('/cb', 'GET');
        $list = [
            'GET' => [
                '/cb' => 'CBClass',
            ],
        ];
        (new Dispatcher())
            ->setContainer($this->getMockContainer(['CBClass' => $endpoint]))
            ->setEndpointList($list)
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->addResponseMiddleware(function ($response, $next) {
                $this->response_hits = 'a';
                return $next($response);
            })
            ->addResponseMiddleware(function ($response, $next) {
                $this->response_hits = 'b';
                return $next($response);
            })
            ->dispatch();
        $this->assertSame(
            'b',
            $this->response_hits,
            'Last provided response middleware wasn\'t fired last'
        );
    }

    public function testResponseMiddlewaresAreShortCircuitable()
    {
        $endpoint = $this->getMockEndpoint();
        $req = $this->getMockRequestWithUriPath('/cb', 'GET');
        $list = [
            'GET' => [
                '/cb' => 'CBClass',
            ],
        ];
        (new Dispatcher())
            ->setContainer($this->getMockContainer(['CBClass' => $endpoint]))
            ->setEndpointList($list)
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->addResponseMiddleware(function ($response, $next) {
                $this->response_hits = 'a';
                return $response;
            })
            ->addResponseMiddleware(function ($response, $next) {
                // This should never hit
                $this->response_hits = 'b';
                return $next($response);
            })
            ->dispatch();
        $this->assertSame(
            'a',
            $this->response_hits,
            'Second callback was fired that should have been bypassed'
        );
    }

    public function testResponseMiddlewaresAreRunOnError()
    {
        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->throwException(new Exception('dummy')));
        $req = $this->getMockRequestWithUriPath('/cb', 'GET');
        $list = [
            'GET' => [
                '/cb' => 'CBClass',
            ],
        ];
        (new Dispatcher())
            ->setContainer($this->getMockContainer(['CBClass' => $endpoint]))
            ->setEndpointList($list)
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->addResponseMiddleware(function ($response, $next) {
                $this->response_hits = 1;
                return $next($response);
            })
            ->dispatch();
        $this->assertSame(
            1,
            $this->response_hits,
            'Second callback was fired that should have been bypassed'
        );
    }

    /**
     * Ensure that if an exception is thrown during execute() and another (or
     * the same) exception is thrown during the subsequent call. Basically, we
     * *want* the error-handling exception to leak, because
     * a) trying to supress it will probably result in undefined behavior, and
     * b) something is deeply broken in the application, which you should know
     *
     * @covers ::dispatch
     */
    public function testErrorInResponseHandler()
    {
        $execute = new Exception('Execute error');
        $error = new Exception('Exception handler error');
        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->once())
            ->method('execute')
            ->will($this->throwException($execute));
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($execute)
            ->will($this->throwException($error));

        $req = $this->getMockRequestWithUriPath('/cb', 'GET');
        $list = [
            'GET' => [
                '/cb' => 'CBClass',
            ],
        ];
        try {
            $ret = (new Dispatcher())
                ->setContainer($this->getMockContainer(['CBClass' => $endpoint]))
                ->setEndpointList($list)
                ->setParserList($this->getDefaultParserList())
                ->setRequest($req)
                ->dispatch();
            $this->fail(
                "The exception thrown from the error handler's failure should ".
                "have made it through"
            );
        } catch (\Throwable $e) {
            $this->assertSame(
                $error,
                $e,
                "Some exception other than the one from the exception handler ".
                "was thrown"
            );
        }
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
        $this->assertSame(
            EndpointFixture::STATUS_ERROR,
            $response->getStatusCode()
        );
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
        $this->assertSame(
            415,
            $response->getStatusCode()
        );
    }

    /**
     * @covers ::dispatch
     */
    public function testMatchingContentTypeWithDirectives()
    {
        $contentType = 'application/json; charset=utf-8';
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        $req->expects($this->any())
            ->method('getHeader')
            ->with('Content-type')
            ->will($this->returnValue([$contentType]));
        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        $this->checkResponse($response, 200);
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
     * @param ResponseInterface $response response to test
     * @param int $expected_code HTTP status code to check for
     */
    private function checkResponse(ResponseInterface $response, int $expected_code)
    {
        $this->assertSame(
            $expected_code,
            $response->getStatusCode(),
            'Incorrect status code in response'
        );
    }

    /**
     * Convenience method to get a mock PSR-7 Request that will itself support
     * returning a mock PSR-7 URI with the provided path, and the HTTP method
     * if provided
     *
     * @param string $uri path component of URI
     * @param string $method optional HTTP method
     * @param array $query_data optional raw, unescaped query string data
     * @return RequestInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockRequestWithUriPath(
        string $uri,
        string $method = 'GET',
        array $query_data = []
    ): RequestInterface {
        $mock_uri = $this->createMock(UriInterface::class);
        $mock_uri->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($uri));
        $mock_uri->method('getQuery')
            ->will($this->returnValue(http_build_query($query_data)));

        $req = $this->createMock(RequestInterface::class);

        $req->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($mock_uri));

        $req->method('getMethod')
            ->will($this->returnValue($method));
        return $req;
    }

    /**
     * Convenience method for mocking an endpoint. The mock has no required or
     * optional inputs.
     *
     * @return EndpointInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockEndpoint(): EndpointInterface
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getRequiredInputs')
            ->will($this->returnValue([]));
        $endpoint->method('getOptionalInputs')
            ->will($this->returnValue([]));
        $endpoint->method('handleException')
            ->will($this->returnValue($this->createMock(ResponseInterface::class)));
        return $endpoint;
    }

    /**
     * Run the endpointwith an empty request
     *
     * @param EndpointInterface $endpoint the endpoint to test
     * @return ResponseInterface the endpoint response
     */
    private function executeMockRequestOnEndpoint(EndpointInterface $endpoint): ResponseInterface
    {
        $req = $this->getMockRequestWithUriPath('/container', 'GET');
        $list = [
            'GET' => [
                '/container' => 'ClassThatDoesNotExist',
            ],
        ];
        $response = (new Dispatcher())
            ->setContainer($this->getMockContainer(['ClassThatDoesNotExist' => $endpoint]))
            ->setEndpointList($list)
            ->setParserList($this->getDefaultParserList())
            ->setRequest($req)
            ->dispatch();
        return $response;
    }

    private function getEndpointListForFixture(): array
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

    private function getDefaultParserList(): string
    {
        // This could also be dynamically built
        return dirname(__DIR__).'/vendor/firehed/input/src/Parsers/__parser_list__.json';
    }

    private function getMockContainer(array $values): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        foreach ($values as $key => $value) {
            $container->method('has')
                ->with($key)
                ->will($this->returnValue(true));
            $container->method('get')
                ->with($key)
                ->will($this->returnValue($value));
        }


        return $container;
    }
}

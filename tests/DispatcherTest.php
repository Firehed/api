<?php

declare(strict_types=1);

namespace Firehed\API;

use Exception;
use Firehed\API\Authentication;
use Firehed\API\Authorization;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Interfaces\ErrorHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * @coversDefaultClass Firehed\API\Dispatcher
 * @covers ::<protected>
 * @covers ::<private>
 */
class DispatcherTest extends \PHPUnit\Framework\TestCase
{

    private $reporting;

    public function setUp()
    {
        $this->reporting = error_reporting();
        error_reporting($this->reporting & ~E_USER_DEPRECATED);
    }

    public function tearDown()
    {
        error_reporting($this->reporting);
    }

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
        } catch (Throwable $e) {
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
    public function testFailedEndpointExecutionReachesEndpointErrorHandler()
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

    /**
     * @covers ::dispatch
     * @covers ::setErrorHandler
     */
    public function testExceptionsReachDefaultErrorHandlerWhenSet()
    {
        $e = new Exception('This should reach the main error handler');
        $res = $this->createMock(ResponseInterface::class);
        $cb = function ($req, $ex) use ($e, $res) {
            $this->assertSame($e, $ex, 'A different exception reached the handler');

            return $res;
        };

        $handler = $this->createMock(ErrorHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->will($this->returnCallback($cb));

        $dispatcher = new Dispatcher();
        $this->assertSame(
            $dispatcher,
            $dispatcher->setErrorHandler($handler),
            'setErrorHandler should return $this'
        );

        $endpoint = $this->getMockEndpoint();
        $endpoint->method('execute')
            ->will($this->throwException($e));
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($e)
            ->will($this->throwException($e));
        $this->executeMockRequestOnEndpoint($endpoint, $dispatcher, ServerRequestInterface::class);
    }

    /**
     * This is a sort of BC-prevention test: in v4, the Dispatcher will only
     * accept a ServerRequestInterface instead of the base-level
     * RequestInterface. The new error handler is typehinted as such. This
     * checks that if the base class was provided to the dispatcher, it
     * shouldn't attempt to use the error handler since it would just result in
     * a TypeError. This will be removed in v4.
     *
     * @covers ::dispatch
     */
    public function testExceptionsLeakWhenRequestIsBaseClass()
    {
        $e = new Exception('This should reach the top level');

        $handler = $this->createMock(ErrorHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $dispatcher = new Dispatcher();
        $dispatcher->setErrorHandler($handler);

        $endpoint = $this->getMockEndpoint();
        $endpoint->method('execute')
            ->will($this->throwException($e));
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($e)
            ->will($this->throwException($e));
        try {
            $this->executeMockRequestOnEndpoint($endpoint, $dispatcher);
            $this->fail('An exception should have been thrown');
        } catch (Throwable $t) {
            $this->assertSame($e, $t);
        }
    }

    /** @covers ::dispatch */
    public function testExceptionsLeakWhenNoErrorHandler()
    {
        $e = new Exception('This should reach the top level');

        $endpoint = $this->getMockEndpoint();
        $endpoint->method('execute')
            ->will($this->throwException($e));
        // This is a quasi-v4 endpoint: one where the endpoint's exception
        // handler just rethrows the exception. This should be the same as not
        // choosing to have an endpoint handle exeptions directly in v4.
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($e)
            ->will($this->throwException($e));

        try {
            $this->executeMockRequestOnEndpoint($endpoint);
            $this->fail('An exception should have been thrown');
        } catch (Throwable $t) {
            $this->assertSame($e, $t, 'A different exception was thrown');
        }
    }

    /** @covers ::setRequest */
    public function testDeprecationWarningIsIssuedWithBaseRequest()
    {
        error_reporting($this->reporting); // Turn standard reporting back on
        $dispatcher = new Dispatcher();
        $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        $dispatcher->setRequest($this->createMock(RequestInterface::class));
    }

    /** @covers ::setRequest */
    public function testDeprecationWarningIsNotIssuedWithServerRequest()
    {
        error_reporting($this->reporting); // Turn standard reporting back on
        $dispatcher = new Dispatcher();
        $dispatcher->setRequest($this->createMock(ServerRequestInterface::class));
        $this->assertTrue(true, 'No error should have been raised');
    }

    /** @covers ::setAuthProviders */
    public function testSetAuthProviders()
    {
        $dispatcher = new Dispatcher();
        $this->assertSame(
            $dispatcher,
            $dispatcher->setAuthProviders(
                $this->createMock(Authentication\ProviderInterface::class),
                $this->createMock(Authorization\ProviderInterface::class)
            ),
            'Dispacher did not return $this'
        );
    }

    /** @covers ::dispatch */
    public function testAuthHappensWhenProvided()
    {
        $authContainer = $this->createMock(ContainerInterface::class);

        $authn = $this->createMock(Authentication\ProviderInterface::class);
        $authn->expects($this->once())
            ->method('authenticate')
            ->willReturn($authContainer);

        $response = $this->createMock(ResponseInterface::class);

        $endpoint = $this->createMock(Interfaces\AuthenticatedEndpointInterface::class);
        $endpoint->expects($this->once())
            ->method('setAuthentication')
            ->with($authContainer);
        $endpoint->expects($this->once())
            ->method('execute')
            ->willReturn($response);

        $authz = $this->createMock(Authorization\ProviderInterface::class);
        $authz->expects($this->once())
            ->method('authorize')
            ->with($endpoint, $authContainer)
            ->willReturn(new Authorization\Ok());


        $dispatcher = new Dispatcher();
        $dispatcher->setAuthProviders($authn, $authz);
        $res = $this->executeMockRequestOnEndpoint($endpoint, $dispatcher, ServerRequestInterface::class);
        $this->assertSame($response, $res);
    }

    /** @covers ::dispatch */
    public function testExecuteIsNotCalledWhenAuthzFails()
    {
        $authContainer = $this->createMock(ContainerInterface::class);
        $authn = $this->createMock(Authentication\ProviderInterface::class);
        $authn->expects($this->once())
            ->method('authenticate')
            ->willReturn($authContainer);

        $authzEx = new Authorization\Exception();

        $endpoint = $this->createMock(Interfaces\AuthenticatedEndpointInterface::class);
        $endpoint->expects($this->never())
            ->method('execute');
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($authzEx)
            ->will($this->throwException($authzEx));

        $authz = $this->createMock(Authorization\ProviderInterface::class);
        $authz->expects($this->once())
            ->method('authorize')
            ->with($endpoint, $authContainer)
            ->will($this->throwException($authzEx));

        $dispatcher = new Dispatcher();
        $dispatcher->setAuthProviders($authn, $authz);
        try {
            $this->executeMockRequestOnEndpoint($endpoint, $dispatcher, ServerRequestInterface::class);
            $this->fail('An authorization exception should have been thrown');
        } catch (Authorization\Exception $e) {
            $this->assertSame($authzEx, $e);
        }
    }

    /** @covers ::dispatch */
    public function testExecuteIsNotCalledWhenAuthnFails()
    {
        $authnEx = new Authentication\Exception();
        $authn = $this->createMock(Authentication\ProviderInterface::class);
        $authn->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException($authnEx));

        $endpoint = $this->createMock(Interfaces\AuthenticatedEndpointInterface::class);
        $endpoint->expects($this->never())
            ->method('execute');
        $endpoint->expects($this->once())
            ->method('handleException')
            ->with($authnEx)
            ->will($this->throwException($authnEx));

        $authz = $this->createMock(Authorization\ProviderInterface::class);
        $authz->expects($this->never())
            ->method('authorize');

        $dispatcher = new Dispatcher();
        $dispatcher->setAuthProviders($authn, $authz);
        try {
            $this->executeMockRequestOnEndpoint($endpoint, $dispatcher, ServerRequestInterface::class);
            $this->fail('An exception should have been thrown');
        } catch (Authentication\Exception $e) {
            $this->assertSame($authnEx, $e);
        }
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
     * @param string $requestClass What RequestInterface to mock
     * @return RequestInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockRequestWithUriPath(
        string $uri,
        string $method = 'GET',
        array $query_data = [],
        string $requestClass = RequestInterface::class
    ): RequestInterface {
        $mock_uri = $this->createMock(UriInterface::class);
        $mock_uri->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($uri));
        $mock_uri->method('getQuery')
            ->will($this->returnValue(http_build_query($query_data)));

        /** @var RequestInterface | \PHPUnit\Framework\MockObject\MockObject */
        $req = $this->createMock($requestClass);

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
     * @param ?Dispatcher $dispatcher a configured dispatcher
     * @return ResponseInterface the endpoint response
     */
    private function executeMockRequestOnEndpoint(
        EndpointInterface $endpoint,
        Dispatcher $dispatcher = null,
        string $requestClass = RequestInterface::class
    ): ResponseInterface {
        $req = $this->getMockRequestWithUriPath('/container', 'GET', [], $requestClass);
        $list = [
            'GET' => [
                '/container' => 'ClassThatDoesNotExist',
            ],
        ];
        if (!$dispatcher) {
            $dispatcher = new Dispatcher();
        }
        $response = $dispatcher
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

<?php

declare(strict_types=1);

namespace Firehed\API;

use Exception;
use Firehed\API\Authentication;
use Firehed\API\Authorization;
use Firehed\API\Enums\HTTPMethod;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Interfaces\HandlesOwnErrorsInterface;
use Firehed\API\Errors\HandlerInterface;
use Firehed\Input\Exceptions\InputException;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use OutOfBoundsException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;
use TypeError;

/**
 * @covers Firehed\API\Dispatcher
 */
class DispatcherTest extends \PHPUnit\Framework\TestCase
{

    /** @var int */
    private $reporting;

    public function setUp(): void
    {
        $this->reporting = error_reporting();
        error_reporting($this->reporting & ~E_USER_DEPRECATED);
    }

    public function tearDown(): void
    {
        error_reporting($this->reporting);
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(
            Dispatcher::class,
            new Dispatcher()
        );
    }

    // ----(Setters)-----------------------------------------------------------

    public function testSetContainerReturnsSelf(): void
    {
        $d = new Dispatcher();
        $container = $this->getMockContainer([]);
        $this->assertSame(
            $d,
            $d->setContainer($container),
            'setContainer did not return $this'
        );
    }

    public function testSetEndpointListReturnsSelf(): void
    {
        $d = new Dispatcher();
        $this->assertSame(
            $d,
            $d->setEndpointList('list'),
            'setEndpointList did not return $this'
        );
    }

    // ----(Success case)-------------------------------------------------------

    /**
     * Test successful all-the-way-through controller execution, including both
     * URL-provided data (regex captures) and POST body.
     *
     */
    public function testDataReachesEndpoint(): void
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        $body = $req->getBody();
        $body->write('shortstring=aBcD');
        $req = $req->withBody($body);
        /** @var ServerRequestInterface */
        $req = $req->withHeader('Content-type', 'application/x-www-form-urlencoded');

        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->dispatch($req);
        $this->checkResponse($response, 200);
        $data = json_decode((string)$response->getBody(), true);
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
     */
    public function testQueryStringDataReachesEndpoint(): void
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath(
            '/user/5',
            'GET',
            ['shortstring' => 'aBcD']
        );

        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->dispatch($req);
        $this->checkResponse($response, 200);
        $data = json_decode((string)$response->getBody(), true);
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
     */
    public function testContainerClassIsPrioritized(): void
    {
        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue(
                $this->createMock(ResponseInterface::class)
            ));
        $this->executeMockRequestOnEndpoint($endpoint, []);
        $this->markTestIncomplete('This needs reworking');
    }

    public function testPsr15(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $modifiedRequest = $this->getMockRequestWithUriPath('/c', 'GET', []);
        $response = $this->createMock(ResponseInterface::class);
        $modifiedResponse = $this->createMock(ResponseInterface::class);

        $dispatcher = new Dispatcher();

        $mw1 = $this->createMock(MiddlewareInterface::class);
        $mw1->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($req, $handler) use ($request, $modifiedRequest) {
                $this->assertSame($req, $request, 'Request mismatch');
                return $handler->handle($modifiedRequest);
            });
        $mw2 = $this->createMock(MiddlewareInterface::class);
        $mw2->expects($this->once())
            ->method('process')
            ->willReturnCallback(function ($req, $handler) use ($modifiedRequest, $response, $modifiedResponse) {
                $this->assertSame($req, $modifiedRequest, 'Request mismatch');
                $endpointResponse = $handler->handle($req);
                $this->assertSame($response, $endpointResponse, 'Response mismatch');
                return $modifiedResponse;
            });


        $endpoint = $this->getMockEndpoint();
        $endpoint->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn($response);

        $routes = [HTTPMethod::GET => ['/c' => 'EP']];
        $res = $dispatcher
            ->addMiddleware($mw1)
            ->addMiddleware($mw2)
            ->setContainer($this->getMockContainer(['EP' => $endpoint]))
            ->setEndpointList($routes)
            ->dispatch($request);

        $this->assertSame($modifiedResponse, $res, 'Dispatcher returned different response');
    }

    /**
     * Ensure that if an exception is thrown during execute() and another (or
     * the same) exception is thrown during the subsequent call. Basically, we
     * *want* the error-handling exception to leak, because
     * a) trying to supress it will probably result in undefined behavior, and
     * b) something is deeply broken in the application, which you should know
     *
     */
    public function testWhenEndpointsOwnErrorHandlerThrows(): void
    {
        $execute = new Exception('Execute error');
        $error = new Exception('Exception handler error');
        $endpoint = new fixtures\ErrorHandlingEndpoint(
            function () use ($execute) {
                throw $execute;
            },
            function ($caught) use ($execute, $error) {
                $this->assertSame($execute, $caught);
                throw $error;
            },
        );

        $req = $this->getMockRequestWithUriPath('/cb', 'GET');
        $list = [
            HTTPMethod::GET => [
                '/cb' => 'CBClass',
            ],
        ];
        try {
            $ret = (new Dispatcher())
                ->setContainer($this->getMockContainer(['CBClass' => $endpoint]))
                ->setEndpointList($list)
                ->dispatch($req);
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

    public function testDispatchThrowsWhenMissingData(): void
    {
        $d = new Dispatcher();
        $this->expectException(InvalidArgumentException::class);
        $d->dispatch($this->getMockRequestWithUriPath('/'));
    }

    public function testNoRouteMatchReturns404(): void
    {
        $req = $this->getMockRequestWithUriPath('/');

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionCode(404);
        $ret = (new Dispatcher())
            ->setEndpointList([]) // No routes
            ->dispatch($req);
    }

    public function testFailedInputValidationCanReachErrorHandlers(): void
    {
        // See tests/EndpointFixture
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        $body = $req->getBody();
        $body->write('shortstring=thisistoolong');
        $req = $req->withBody($body);
        /** @var ServerRequestInterface */
        $req = $req->withHeader('Content-type', 'application/x-www-form-urlencoded');

        try {
            $response = (new Dispatcher())
                ->setEndpointList($this->getEndpointListForFixture())
                ->dispatch($req);
            $this->fail('An exception should have been thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(InputException::class, $e);
        }
    }

    public function testUnsupportedContentTypeCanReachErrorHandlers(): void
    {
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        /** @var ServerRequestInterface */
        $req = $req->withHeader('Content-type', 'application/x-test-failure');
        try {
            $response = (new Dispatcher())
                ->setEndpointList($this->getEndpointListForFixture())
                ->dispatch($req);
            $this->fail('An exception should have been thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertSame(415, $e->getCode());
        }
    }

    public function testMatchingContentTypeWithDirectives(): void
    {
        $contentType = 'application/json; charset=utf-8';
        $req = $this->getMockRequestWithUriPath('/user/5', 'POST');
        /** @var ServerRequestInterface */
        $req = $req->withHeader('Content-type', $contentType);
        $response = (new Dispatcher())
            ->setEndpointList($this->getEndpointListForFixture())
            ->dispatch($req);
        $this->checkResponse($response, 200);
    }

    public function testFailedEndpointExecutionReachesEndpointErrorHandler(): void
    {
        $e = new Exception('This should reach the error handler');
        $endpoint = new fixtures\ErrorHandlingEndpoint(
            function () use ($e) {
                throw $e;
            },
            function ($caught) use ($e) {
                $this->assertSame($e, $caught);
                return $this->createMock(ResponseInterface::class);
            },
        );
        $this->executeMockRequestOnEndpoint($endpoint, []);
    }


    public function testScalarResponseFromEndpointReachesErrorHandler(): void
    {
        $endpoint = new fixtures\ErrorHandlingEndpoint(
            fn () => false, // Trigger TypeError
            function ($caught) {
                $this->assertInstanceOf(TypeError::class, $caught);
                return $this->createMock(ResponseInterface::class);
            },
        );
        $this->executeMockRequestOnEndpoint($endpoint, []);
    }

    public function testInvalidTypeResponseFromEndpointReachesErrorHandler(): void
    {
        $endpoint = new fixtures\ErrorHandlingEndpoint(
            fn () => new \DateTime(), // Trigger TypeError
            function ($caught) {
                $this->assertInstanceOf(TypeError::class, $caught);
                return $this->createMock(ResponseInterface::class);
            },
        );
        $this->executeMockRequestOnEndpoint($endpoint, []);
    }

    public function testErrorHandlerHandlesExceptionFromEndpointExecute(): void
    {
        $ex = new Exception('execute');
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->will($this->returnCallback(function ($sri, $caught) use ($ex) {
                $this->assertSame($ex, $caught);
                return $this->createMock(ResponseInterface::class);
            }));

        $ep = $this->createMock(EndpointInterface::class);
        $ep->method('execute')
            ->will($this->throwException($ex));

        $container = [
            HandlerInterface::class => $handler,
        ];
        $this->executeMockRequestOnEndpoint($ep, $container);
    }

    public function testErrorHandlerHandles404(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->will($this->returnCallback(function ($request, $exception) use ($response) {
                $this->assertInstanceOf(OutOfBoundsException::class, $exception);
                $this->assertSame(404, $exception->getCode());
                return $response;
            }));
        $container = $this->getMockContainer([
            HandlerInterface::class => $handler,
        ]);

        $request = $this->getMockRequestWithUriPath('/');
        $finalResponse = (new Dispatcher())
            ->setEndpointList([])
            ->setContainer($container)
            ->dispatch($request);
        $this->assertSame($response, $finalResponse);
    }

    public function testExceptionsFromEndpointsOwnHandlerReachDefaultHandlerWhenSet(): void
    {
        $first = new Exception('This is the initially thrown exception');
        $second = new Exception('This should reach the main error handler');
        $res = $this->createMock(ResponseInterface::class);
        $cb = function ($req, $ex) use ($second, $res) {
            $this->assertSame($second, $ex, 'A different exception reached the handler');

            return $res;
        };

        $handler = $this->createMock(HandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->will($this->returnCallback($cb));

        $container = [
            HandlerInterface::class => $handler,
        ];

        $endpoint = new fixtures\ErrorHandlingEndpoint(
            function () use ($first) {
                throw $first;
            },
            function ($caught) use ($first, $second) {
                $this->assertSame($first, $caught);
                throw $second;
            },
        );
        $this->executeMockRequestOnEndpoint($endpoint, $container);
    }

    public function testExceptionsLeakWhenNoErrorHandler(): void
    {
        $e = new Exception('This should reach the top level');

        $endpoint = $this->getMockEndpoint();
        $endpoint->method('execute')
            ->will($this->throwException($e));

        try {
            $this->executeMockRequestOnEndpoint($endpoint, []);
            $this->fail('An exception should have been thrown');
        } catch (Throwable $t) {
            $this->assertSame($e, $t, 'A different exception was thrown');
        }
    }

    public function testAuthHappensWhenProvided(): void
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

        $container = [
            Authentication\ProviderInterface::class => $authn,
            Authorization\ProviderInterface::class => $authz,
        ];

        $res = $this->executeMockRequestOnEndpoint($endpoint, $container);

        $this->assertSame($response, $res);
    }

    public function testExecuteIsNotCalledWhenAuthzFails(): void
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

        $authz = $this->createMock(Authorization\ProviderInterface::class);
        $authz->expects($this->once())
            ->method('authorize')
            ->with($endpoint, $authContainer)
            ->will($this->throwException($authzEx));

        $container = [
            Authentication\ProviderInterface::class => $authn,
            Authorization\ProviderInterface::class => $authz,
        ];
        try {
            $this->executeMockRequestOnEndpoint($endpoint, $container);
            $this->fail('An authorization exception should have been thrown');
        } catch (Authorization\Exception $e) {
            $this->assertSame($authzEx, $e);
        }
    }

    public function testExecuteIsNotCalledWhenAuthnFails(): void
    {
        $authnEx = new Authentication\Exception();
        $authn = $this->createMock(Authentication\ProviderInterface::class);
        $authn->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException($authnEx));

        $endpoint = $this->createMock(Interfaces\AuthenticatedEndpointInterface::class);
        $endpoint->expects($this->never())
            ->method('execute');

        $authz = $this->createMock(Authorization\ProviderInterface::class);
        $authz->expects($this->never())
            ->method('authorize');

        $container = [
            Authentication\ProviderInterface::class => $authn,
            Authorization\ProviderInterface::class => $authz,
        ];
        try {
            $this->executeMockRequestOnEndpoint($endpoint, $container);
            $this->fail('An exception should have been thrown');
        } catch (Authentication\Exception $e) {
            $this->assertSame($authnEx, $e);
        }
    }

    public function testDispatchRunsMiddlewareOnSubsequentRequests(): void
    {
        $mw = new fixtures\Middleware();
        $ep = $this->getMockEndpoint();

        $dispatcher = new Dispatcher();
        $dispatcher->addMiddleware($mw);

        $this->assertSame(0, $mw->getProcessedCount(), 'MW should not be called yet');
        $this->executeMockRequestOnEndpoint($ep, [], $dispatcher);
        $this->assertSame(1, $mw->getProcessedCount(), 'MW should be called once');
        $this->executeMockRequestOnEndpoint($ep, [], $dispatcher);
        $this->assertSame(2, $mw->getProcessedCount(), 'MW should be called twice');
    }

    // ----(Helper methods)----------------------------------------------------

    /**
     * @param ResponseInterface $response response to test
     * @param int $expected_code HTTP status code to check for
     */
    private function checkResponse(ResponseInterface $response, int $expected_code): void
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
     * @param mixed[] $query_data optional raw, unescaped query string data
     * @return ServerRequestInterface
     */
    private function getMockRequestWithUriPath(
        string $uri,
        string $method = 'GET',
        array $query_data = []
    ): ServerRequestInterface {
        $uri .= '?' . http_build_query($query_data);
        $request = new ServerRequest(
            $method,
            $uri
        );
        return $request;
    }

    /**
     * Convenience method for mocking an endpoint. The mock has no required or
     * optional inputs.
     *
     * @return EndpointInterface & \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockEndpoint(): EndpointInterface
    {
        /** @var EndpointInterface & \PHPUnit\Framework\MockObject\MockObject */
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getRequiredInputs')
            ->will($this->returnValue([]));
        $endpoint->method('getOptionalInputs')
            ->will($this->returnValue([]));
        return $endpoint;
    }

    /**
     * Run the endpoint with an empty request
     *
     * @param EndpointInterface $endpoint the endpoint to test
     * @param mixed[] $containerValues Additional container values
     * @param ?Dispatcher $dispatcher a configured dispatcher
     * @return ResponseInterface the endpoint response
     */
    private function executeMockRequestOnEndpoint(
        EndpointInterface $endpoint,
        array $containerValues,
        Dispatcher $dispatcher = null
    ): ResponseInterface {
        $req = $this->getMockRequestWithUriPath('/container', 'GET', []);
        $list = [
            HTTPMethod::GET => [
                '/container' => 'ClassThatDoesNotExist',
            ],
        ];
        if (!$dispatcher) {
            $dispatcher = new Dispatcher();
        }

        // Add endpoint definition to container
        $containerValues['ClassThatDoesNotExist'] = $endpoint;

        $response = $dispatcher
            ->setContainer($this->getMockContainer($containerValues))
            ->setEndpointList($list)
            ->dispatch($req);
        return $response;
    }

    /**
     * @return array<HTTPMethod::*, array<string, class-string<EndpointInterface>>>
     */
    private function getEndpointListForFixture(): array
    {
        return [
            HTTPMethod::GET => [
                '/user/(?P<id>[1-9]\d*)' => __NAMESPACE__.'\EndpointFixture'
            ],
            HTTPMethod::POST => [
                '/user/(?P<id>[1-9]\d*)' => __NAMESPACE__.'\EndpointFixture'
            ],
        ];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getMockContainer(array $values): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturnCallback(function ($id) use ($values) {
                return array_key_exists($id, $values);
            });
        $container->method('get')
            ->willReturnCallback(function ($id) use ($values) {
                return $values[$id];
            });
        return $container;
    }
}

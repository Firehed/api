<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Authentication;

use BadMethodCallException;
use Firehed\API\Traits;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Containers\SafeInput;
use Psr\Http\Message;
use RuntimeException;

/**
 * @coversDefaultClass Firehed\API\Traits\Authentication\BearerToken
 * @covers ::<protected>
 * @covers ::<private>
 */
class BearerTokenTest extends \PHPUnit\Framework\TestCase
{

    private $calledWithEndpoint;
    private $calledWithToken;

    /**
     * @covers ::authenticate
     * @covers ::setHandleBearerTokenCallback
     * @dataProvider bearerTokens
     */
    public function testAuthenticate($token)
    {
        $endpoint = $this->getEndpoint();
        $request = $this->getRequest(sprintf('Bearer %s', $token));

        $this->assertEquals(
            $endpoint,
            $endpoint->authenticate($request),
            'authenticate did not return $this'
        );
        // The injected callback will set the instance variables, so this is
        // asserting the callback was called with the correct parameters
        $this->assertSame($token, $this->calledWithToken);
        $this->assertSame($endpoint, $this->calledWithEndpoint);
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateWithNoAuthorizationHeader()
    {
        $endpoint = $this->getEndpoint();
        $request = $this->getRequest(null);

        $this->expectException(RuntimeException::class);
        $endpoint->authenticate($request);
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateWithNonBearerHeader()
    {
        $endpoint = $this->getEndpoint();
        $request = $this->getRequest('Basic QWxhZGRpbjpPcGVuU2VzYW1l');

        $this->expectException(RuntimeException::class);
        $endpoint->authenticate($request);
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateWithBadBearerHeader()
    {
        $endpoint = $this->getEndpoint();
        $request = $this->getRequest('Bearer ');

        $this->expectException(RuntimeException::class);
        $endpoint->authenticate($request);
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticateFailsWithNoCallbackProvided()
    {
        $endpoint = $this->getEndpoint(false);
        $request = $this->getRequest('Bearer sometoken');

        $this->expectException(BadMethodCallException::class);
        $endpoint->authenticate($request);
    }

    public function bearerCallback(EndpointInterface $endpoint, string $token)
    {
        $this->calledWithEndpoint = $endpoint;
        $this->calledWithToken = $token;
    }

    private function getEndpoint($setCallback = true): EndpointInterface
    {
        $endpoint = new class implements EndpointInterface {
            use BearerToken;
            use Traits\Request\Get;
            use Traits\Input\NoRequired;
            use Traits\Input\NoOptional;
            function getUri(): string
            {
            }
            function handleException(\Throwable $e): Message\ResponseInterface
            {
            }
            function execute(SafeInput $input): Message\ResponseInterface
            {
            }
        };
        if ($setCallback) {
            $endpoint->setHandleBearerTokenCallback([$this, 'bearerCallback']);
        }
        return $endpoint;
    }

    private function getRequest(string $headerValue = null): Message\RequestInterface
    {
        $request = $this->createMock(Message\RequestInterface::class);
        $request->expects($this->any())
            ->method('getHeaderLine')
            ->with('Authorization')
            ->will($this->returnValue($headerValue));
        return $request;
    }

    // Data provider for testAuthenticate
    public function bearerTokens(): array
    {
        return [
            ['sometoken'],
            ['some token with spaces'],
            ['mF_9.B5f-4.1JqM'], // RFC 6750 example
            ['AlPhA12345-._~+/A=='], // RFC all characters
            ['TTUnbkBtyuunjYKTpodFGUCxeML7HArbFgQF+/Q0fZ2pzfc='], // random base64
            ['eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1aWQiOjF9.poptiU4cVJayalWC_n2zGrb1_6Rnzd48TbWLbpsu7lM'], // JWT
        ];
    }
}

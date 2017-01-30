<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Authentication;

use BadMethodCallException;
use Firehed\API\Interfaces\EndpointInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * Trait to implement OAuth2 Bearer Token authentication
 *
 * Complies with RFC 6750
 *
 * Using this trait still requires you to inject a handler for the token
 * itself, since it's unaware of the application at large. This just parses the
 * HTTP request and extracts the bearer token, handling invalid or missing
 * values. Provide said handler with `setHandleBearerTokenCallback()`.
 */
trait BearerToken
{

    private $handleBearerTokenCallback;

    public function authenticate(RequestInterface $request): EndpointInterface
    {
        if (!$this->handleBearerTokenCallback) {
            throw new BadMethodCallException(
                'There is no callback to handle the Bearer token. Call '.
                '`::setHandleBearerTokenCallback` first');
        }

        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            throw new RuntimeException('Empty "Authorization" header', 401);
        }
        if (0 !== strpos($auth, 'Bearer ')) {
            throw new RuntimeException(
                'Authorization header is not a valid bearer token', 401);
        }

        list($bearer, $token) = explode(' ', $auth, 2);
        $token = trim($token);
        if (!$token) {
            throw new RuntimeException(
                'No bearer token present in Authorization header', 401);
        }

        $callback = $this->handleBearerTokenCallback;

        $callback($this, $token);

        return $this;
    }

    /**
     * Inject the callback to process the bearer token. This will normally
     * search a database for the bearer token, load the associated grant or
     * user, and push that user back into the endpoint.
     *
     * @param callable $callback The callback should run. Should have the
     * signature `function(EndpointInterface $endpoint, string $token): void`
     *
     * @return this
     */
    public function setHandleBearerTokenCallback(callable $callback)
    {
        $this->handleBearerTokenCallback = $callback;
        return $this;
    }

}

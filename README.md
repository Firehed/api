# API Framework

[![Build Status](https://travis-ci.org/Firehed/api.svg?branch=master)](https://travis-ci.org/Firehed/api)
[![codecov](https://codecov.io/gh/Firehed/api/branch/master/graph/badge.svg)](https://codecov.io/gh/Firehed/api)


## Installation

API is available via composer:

`composer require firehed/api`

## Usage

Set up an `.apiconfig` file, which contains JSON-formatted settings for the framework.
See configuration below for additional information.

Generate a default front-controller:

`./vendor/bin/generate_front_controller`

After creating, modifying, or deleting endpoints, run the compiler:

`vendor/bin/api compile:all`

This step is _not_ optional: the framework depends on the generated files, rather than ever attempting to perform the same step at runtime.
It is expected that you will rebuild the files on every build/deployment with the above command.
See the section on best practices below.

## Testing

For convenience, a trait is included that includes tests for the description methods of your endpoints.
In your test case class (which typically extends `PHPUnit\Framework\TestCase`, use the trait:

`use Firehed\API\Traits\EndpointTestCases`

And add a `getEndpoint` method that returns an instance of the endpoint under test.

Be sure to add a `@coversDefaultClass` annotation to the test case - `@covers` annotations are present in all of the trait tests for code coverage reports.

### Example

```php
<?php

namespace MyApp\API\Endpoints\User;

use Firehed\API\Traits\EndpointTestCases;

/**
 * @coversDefaultClass MyApp\API\Endpoints\User\Create
 * @covers ::<protected>
 * @covers ::<private>
 */
class CreateTest extends \PHPUnit\Framework\TestCase
{

    use EndpointTestCases;

    protected function getEndpoint()
    {
        return new Create();
    }
}
```

## Configuration

Place a file named `.apiconfig` in your project root.
It uses JSON for the format.

### Options

`source`: **required** *string*

The source code directory to scan for API endpoints.
Most commonly `src`.

`namespace`: **required** *string*

A namespace to filter on when searching for endpoints.

`webroot`: **required** *string*

The directory to place a generated front controller.
The value should be relative to the project root.

`container`: **optional** *string*

The path to a file which returns a `PSR-11`-compliant container for config values.

### Container

If you set a `container` value in `.apiconfig`, the API will be made aware of the container (if you do not use the generated front controller, you may also do this manually).
This is how to configure API endpoints at runtime.
By convention, if the container `has()` an endpoint's fully-qualified class name, the dispatcher will `get()` and use that value when the route is dispatched.
If no container is configured, or the container does not have a configuration for the routed endpoint, the routed endpoint will simply be instantiated via `new $routedEndpointClassName`.

Other auto-detected container entries:

| Key | Usage | Detected |
|---|---|---|
| Psr\Log\LoggerInterface | Internal logging | generated front controller |
| Firehed\API\Authentication\ProviderInterface | Authentication Provider | Always if an AuthorizationProvider is set |
| Firehed\API\Authorization\ProviderInterface | Authorization Provider | Always if an AuthenticationProvider is set |
| Firehed\API\Errors\HandlerInterface | Error Handler | Always |


### Example

`.apiconfig`:

```json
{
    "webroot": "public",
    "namespace": "Your\\Application",
    "source": "src",
    "container": "config.php"
}
```

`config.php`:

```php
<?php
use Firehed\API;
use Psr\Log\LoggerInterface;
use Your\Application\Endpoints;

$container = new Pimple\Container();
// Endpoint config
$container[Endpoints\UserPost::class] = function ($c) {
    return new Endpoints\UserPost($c['some-dependency']);
};

// Other services
$container[API\Authentication\ProviderInterface::class] = function ($c) {
    // return your provider
};
$container[API\Authorization\ProviderInterface::class] = function ($c) {
    // return your provider
};
$container[LoggerInterface::class] = function ($c) {
    return new Monolog\Logger('your-application');
};

// ...
return new Pimple\Psr11\Container($container);
```

In this example, when your `UserPost` endpoint is routed, it will use the endpoint defined in the container - this allows for endpoints with required constructor arguments or other configuration.

If you have e.g. a `UserGet` endpoint which is _not_ in the container, the dispatcher will automatically attempt to instantiate it with `new`.
If that endpoint has no constructor arguments, this will be fine.
However, this means your application will crash at runtime if it does - so any endpoints with required constructors **must** be configured in the container.

## Authentication and Authorization

There are two interfaces defined for the processes of authentication (who is performing the request) and authorization (whether they are allowed to perform the request), respectively named `Authentication\ProviderInterface` and `Authorization\ProviderInterface`.
Both interfaces will be autodetected in a container or can be explicitly provided via `Dispatcher::setAuthProviders()`.
If these are not provided, **no authentication or authorization will ever be performed using the application-wide handlers**.

Any endpoint that implements `Interfaces\AuthenticatedEndpointInterface` will have these processes performed prior to execution, and the container returned by the Authentication provider will be made available to it.
If an endpoint does not implement `Interfaces\AuthenticatedEndpointInterface` (i.e. it only implements `Interfaces\EndpointInterface`), **application-wide auth will be skipped**.
Endpoints may, of course, choose to implement their own auth protocols in their `execute()` method, but this is discouraged, with the exception of login-type pages (see below).

Generally speaking, implementations for the above interfaces should be looking for authentication data present in (almost) every request: cookies, OAuth Bearer tokens, HTTP basic auth, etc., and validating their authenticity.
Endpoints that are used to obtain auth data (e.g. OAuth grant) typically will NOT be authenticated themselves, but will set or return the data to be used to authenticate other requests.

Example provider, which implements both interfaces:

```php
<?php
declare(strict_types=1);

namespace Your\Project;

use Firehed\API\Authentication\ProviderInterface as AuthnProvider;
use Firehed\API\Authorization\Exception as AuthException;
use Firehed\API\Authorization\ProviderInterface as AuthzProvider;
use Firehed\API\Authorization\Ok;
use Firehed\API\Container;
use Firehed\API\Interfaces\AuthenticatedEndpointInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

// This elides a lot of details and error handling for simplicity
class AuthProvider implements AuthnProvider, AuthzProvider
{
    public function authenticate(ServerRequestInterface $request): ContainerInterface
    {
        list($_, $token) = explode(' ', $request->getHeaderLine('Authorization'), 2);
        // Find a user, app, etc. from the token string
        return new Container([
            App::class => $app,
            User::class => $user,
            // ...
            'oauth_scopes' => $scopes,
        ]);
    }

    public function authorize(AuthenticatedEndpointInterface $endpoint, ContainerInterface $container): Ok
    {
        $scopes = $container->get('oauth_scopes');
        if (!$endpoint instanceof YourInternalScopeInterface) {
            throw new \LogicException('Endpoint is invalid');
        }
        // This is a method in YourInternalScopeInterface
        $neededScopes = $endpoint->getRequiredScopes();
        foreach ($neededScopes as $scope) {
            if (!in_array($scope, $scopes)) {
                throw new AuthException(sprintf('Missing scope %s', $scope));
            }
        }
        return new Ok();
    }
}
```

## Error Handling

It is strongly discouraged to handle most exceptions that are thrown in an Endpoint's `execute()` method.
Instead, prefer to write services that fail loudly by throwing exceptions and endpoints that expect the success case.
This is not an absolute rule, but helps avoid deeply-nested `try`/`catch` blocks and other complexity around error handling.

The API framework is responsible for catching all exceptions thrown during an Endpoint's `execute()` method, and will provide them to dedicated exception handlers.

All endpoints that implement `Firehed\API\Interfaces\HandlesOwnErrorsInterface` (which is a part of `EndpointInterface` prior to v4.0.0) will have their `handleException()` method called with the thrown exception.
This method _may_ choose to ignore certain exception classes (by rethrowing them), but must return a PSR `ResponseInterface` when opting to handle an exception.

Starting in v3.1.0, error handling can be implemented globally by providing a `Firehed\API\Errors\HandlerInterface` to the Dispatcher via `setErrorHandler()`.
This is functionally identical to `HandlesOwnErrorInterface` described above, with the addition that the PSR `ServerRequestInterface` will also be available (primarily so that the response can be formatted appropriately for the request, e.g. based on the `Accept` header).

Finally, a global fallback handler is configured by default, which will log the exception and return a generic 500 error.

## Best Practices

### Source Control

Use source control, of course.

The following patterns should be added to your source control's ignored files, to exclude generated files:

- `__*__.*`

### Build Automation

It is highly recommended (for any modern PHP application) to use automated builds.

This framework relies on compilation in order to improve performance.
You **must** run the compilation process prior to deployment, and **should** do so during your automated build:

`vendor/bin/api compile:all`.

### Docker

There are no special requirements to run in Docker, beyond what is noted in the above build automation section.

This means you should have the following line in your Dockerfile at any point after installing dependencies with Composer:

```Dockerfile
RUN vendor/bin/api compile:all
```

You **should** also add all of the source control ignore files to your `.dockerignore`.

## Compatibility

This framework tries to strictly follow the rules of Semantic Versioning.
In summary, this means that given a release named `X.Y.Z`:

- Breaking changes will only be introduced when `X` is incremented
- New features will only be introduced either when `Y` is incremented or when `X` is incremented and `Y` is reset to `0`
- Bugfixes may be introduced in any version increment

The term "breaking changes" should be interpreted to mean:

- Additional required parameters being added to methods
- Additional methods being added to interfaces
- Tightening the typehints of a method or function parameter
- Loosening the return type of a method or function
- Deletion of any public method (except on classes marked as internal)
- Additional system requirements (PHP version, extensions, etc.)
- Substantial, non-optional behavior changes
- Required modifications to documented build steps (e.g. `vendor/bin/api compile:all`)

Breaking changes DO NOT include:

- Removal of a dependency (if you are implicitly relying on a dependency of this framework, you should explicitly add it into your own `composer.json`)
- Removal of a class or method that is clearly marked internal
- Format or content changes to any files that are intended to be generated during the compilation process, including adding or removing files entirely

Whenever possible, deprecated functionality will be marked as such by `trigger_error(string, E_USER_DEPRECATED)` (in addition to release notes).
Note that depending on your PHP settings, this may result in an `ErrorException` being thrown.
Since that is a configurable behavior, it is NOT considered to be a BC break.

Additionally, the entire `Firehed\API` namespace should be considered reserved for the purposes of PSR-11 Container auto-detection.
That is to say, if you use a key starting with `Firehed\API` in your container, you should expect that key may be retrieved and used without explicitly opting-in to the behavior it provides.

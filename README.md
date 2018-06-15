# API Framework

[![Build Status](https://travis-ci.org/Firehed/api.svg?branch=master)](https://travis-ci.org/Firehed/api)
[![codecov](https://codecov.io/gh/Firehed/api/branch/master/graph/badge.svg)](https://codecov.io/gh/Firehed/api)


## Installation

API is available via composer:

`composer require firehed/api`

## Usage

Set up an `.apiconfig` file, which contains JSON-formatted settings for the
framework.
See configuration below for additional information.

Generate a default front-controller:

`./vendor/bin/generate_front_controller`

After creating, modifying, or deleting endpoints, (re-)run the endpoint mapper:

`./vendor/bin/generate_endpoint_list`

If you have an automated build/deployment process, you should `gitignore` the generated files and run this script during that process. Otherwise you must run it manually and check in the changes.

## Testing

For convenience, a trait is included that includes tests for the description
methods of your endpoints. In your test case class (which typically extends
`PHPUnit\Framework\TestCase`, use the trait:

`use Firehed\API\Traits\EndpointTestCases`

And add a `getEndpoint` method that returns an instance of the endpoint under
test.

Be sure to add a `@coversDefaultClass` annotation to the test case
- `@covers` annotations are present in all of the trait tests for code coverage
reports.

### Example

    <?php

    namespace MyApp\API\Endpoints\User;

    use Firehed\API\Traits\EndpointTestCases;

    /**
     * @coversDefaultClass MyApp\API\Endpoints\User\GetUserEndpoint
     * @covers ::<protected>
     * @covers ::<private>
     */
    class CreateTest extends \PHPUnit\Framework\TestCase
    {

        use EndpointTestCases;

        protected function getEndpoint() {
            return new GetUserEndpoint();
        }
    }
## Configuration

Place a file named `.apiconfig` in your project root. It uses JSON for the format.

### Options

`source`: **required** *string*

The source code directory to scan for API endpoints. Most commonly `src`.

`namespace`: **required** *string*

A namespace to filter on when searching for endpoints.

`webroot`: **required** *string*

The directory to place a generated front controller. The value should be relative to the prohect root.

`container`: **optional** *string*

The path to a file which returns a `PSR-11`-compliant container for config values.

### Container

If you set a `container` value in `.apiconfig`, the API will be made aware of the container (if you do not use the generated front controller, you may also do this manually).
This is how to configure API endpoints at runtime.
By convention, if the container `has()` an endpoint's fully-qualified class name, the dispatcher will `get()` and use that value when the route is dispatched.
If no container is configured, or the container does not have a configuration for the routed endpoint, the routed endpoint will simply be instanciated via `new $routedEndpointClassName`.

Other auto-detected container entries:

| Key | Usage | Detected |
|---|---|---|
| Psr\Log\LoggerInterface | Internal logging | generated front controller |
| Firehed\API\Authentication\ProviderInterface | Authentication Provider | Always if an AuthorizationProvider is set |
| Firehed\API\Authorization\ProviderInterface | Authorization Provider | Always if an AuthenticationProvider is set |


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
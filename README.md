# API Framework

[![Build Status](https://travis-ci.org/Firehed/api.svg?branch=master)](https://travis-ci.org/Firehed/api)
[![Coverage Status](https://coveralls.io/repos/github/Firehed/api/badge.svg?branch=master)](https://coveralls.io/github/Firehed/api?branch=master)

## Installation

API is available via composer:

`composer require firehed/api`

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

If you set a `container` value in `.apiconfig`, the API will be made aware of the container.
This is how to configure API endpoints at runtime.
By convention, if the container `has()` an endpoint's fully-qualified class name, the dispatcher will `get()` and use that value when the route is dispatched.
If no container is configured, or the container does not have a configuration for the routed endpoint, the routed endpoint will simply be instanciated via `new $routedEndpointClassName`.

Further, if your container `has()` a `Psr\Log\LoggerInterface`, the default error handler will automatically be configured to use it.
If it does not, it will use `Psr\Log\NullLogger`, resulting in no logs being written anywhere.
It is therefore *highly recommended* to provide a `PSR-3` logger through the container.

### `.apiconfig` example

```json
{
    "webroot": "public",
    "namespace": "Company\\Project",
    "source": "src",
    "container": "config/config.php"
}
```

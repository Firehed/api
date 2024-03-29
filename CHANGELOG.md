# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [4.0.0] - Unreleased
### Summary of Breaking Changes
- PHP 7.4 or later is now required in Composer. This is in line with PHP's own currently supported versions (#33)
- Traits deprecated in 3.0.0 have been removed (renamed equivalents were added in the same release)
- `renderResponse` has been removed (replaced by `ResponseRenderer`)
- Legacy "response" middleware support has been removed. Only PSR-15 middleware is supported.
- `Dispatcher::setAuthProviders()` and `::setErrorHandler` have been removed. You must provide them with a container now, and they will be fetched lazily.
- `Dispatcher::setRequest(ServerRequestInterface)` has been removed, and the request is now provided directly to `::dispatch(ServerRequestInterface)`
- `HandlesOwnErrorsTestCases::getEndpoint()` has been renamed to `HandlesOwnErrorsTestCases::getErrorHandlingEndpoint()`.
  This prevents an incompatibility in PHP8 from abstract trait method validation colliding with LSP enforcement.
- `ResponseBuilder` trait has been removed. Instead, opt to use a PSR-7 implementation of your choosing.
- Coverage annotations in exported tests traits have been adjusted.
  Instead of using `@coversDefaultClass` on test cases using those traits, use `@covers`.

### Added
- `Traits\EndpointTestCases::getSafeInput()`
- `Traits\EndpointTestCases` now also `use`s `Firehed\Input\SafeInputTestTrait`. This adds `::getSafeInputFromData()` but will result in an import conflict for test cases that have already opted to use the same trait
- Console command: `vendor/bin/api compile:all`
- Console command: `vendor/bin/api generate:config`
- Console command: `vendor/bin/api generate:endpoint`
- Console command: `vendor/bin/api generate:frontController`
- Greatly improved support for use in long-running processes, like ReactPHP
- Expanded support for psr/container to allow both 1.x and 2.x

### Changed
- Files generated during compilation are now expected to be excluded from version control and generated during automated build processes (#84)
- Framework-generated files are now auto-detected thanks to the above compile requirement (#84)
- `Dispatcher::setEndpointList()` and `Dispatcher::setParserList()` are now internal use only, and are no longer called in the generated front controller (#84)
- `Dispatcher::dispatch()` now requires `ServerRequestInterface` as a parameter. This replaces `setRequest` (#101)
- The body parser list (based on MIME-types) is now explicitly hardcoded.
  Previously this was tied to a scanned vendor directory, so in practice nothing has changed.
  This may become configurable in the future.
- Dispatcher::ENDPOINT_LIST has been marked internal

### Deprecated
- Direct use of the HTTPMethod class is considered deprecated.
  Endpoints are _highly_ encouraged to use the corresponding traits (`Traits\Request\...`) instead.
  This will greatly ease the transition to native `Enum`s in PHP 8.1.

### Removed
- Disallowed using `RequestInterface` in `Dispatcher`.
  `ServerRequestInterface` is now required - the base `RequestInterface` is no longer supported.
- `renderResponse()` function
- `Dispatcher::addResponseMiddleware()` (use addMiddleware with PSR-15 MW)
- `Dispatcher::setAuthProviders()` (use setContainer)
- `Dispatcher::setErrorHandler()` (use setContainer)
- `Dispatcher::setRequest()` (provide the request directly to `::dispatch()`)
- `Dispatcher::PARSER_LIST` constant
- `Interfaces\EndpointInterface::authenticate()` - this drops legacy authentication support entirely, and will no longer be used even if still defined in implementing classes
- `Traits\Authentication\BearerToken`
- `Traits\DeleteRequest`
- `Traits\GetRequest`
- `Traits\NoOptionalInputs`
- `Traits\NoRequiredInputs`
- `Traits\PostRequest`
- `Traits\PutRequest`
- `Traits\ResponseBuilder`

## [3.2.1] - 2018-10-24
### Summary
- Widen range of supported Zend Diactoros version

## [3.2.0] - 2018-09-19
### Summary
- Added support for `PATCH` HTTP method

### Added
- `Traits\Request\Patch`

## [3.1.0] - 2018-07-01
### Summary
- Overhauled authentication (#43)
- Overhauled error handling (#37, #38, #63)
- Added support for PSR-15 Middleware (#59)
- Added additional documentation in the README (#66)

### Added
- `Authentication\ProviderInterface`
- `Authorization\ProviderInterface`
- `Errors\HandlerInterface`
- `Interfaces\AuthenticatedEndpointInterface`
- `Interfaces\HandlesOwnErrorsInterface`

### Changed
- Internal refactoring
- If a RequestInterface object is provided to the dispatcher, it will be internally converted to a ServerRequestInterface to ensure compatibility with Middleware and error handling.
  Relying on this functionality is deprecated from the start, **highly** discouraged, and may be imperfect.

### Deprecated
- Deprecated `ErrorHandler` (#37)
- Deprecated use of base RequestInterface (#48)
- Deprecated the `BearerToken` authentication trait (#73)

## [3.0.6] - 2018-04-30
### Changed
- Fixed incorrect return type

## [3.0.5] - 2018-04-03
### Changed
- Removes the use of `fguillot/simplelogger`, since it has been abandoned. This is not treated as a breaking change since it's not safe to _implicitly_ rely on a dependency's dependencies.

## [3.0.4] - 2018-02-23
### Added
- OPTIONS requests are now supported

## [3.0.3] - 2018-01-08

### Changed
- Fixed issue where `Content-type` headers with directives (e.g. `Content-type: application/json; charset=utf-8`) are processed correctly

## [3.0.2] - 2018-01-03

### Changed
- Added URI matching tests into `EndpointTestCases`. Updating to this version will result in existing passing tests using said trait being skipped until good and bad URI matches are added into the test case.

## [3.0.1] - 2017-12-01

### Changed
- Fixed minor issue where variables with certain names defined in the included configuration container's file could impact the code generation scripts
- Fixed issue in generated front controller where config file would be loaded twice

## [3.0.0] - 2017-12-01

### Summary of Breaking Changes

- Containers injected into the `Dispatcher` must now be PSR-11 compliant
- `EndpointTestTrait` renamed to `Traits\EndpointTestCases`

### Added
- Added (and backfilled) this change log
- Added `ResponseBuilder` trait. It adds `emptyResponse()`, `htmlResponse()`, `jsonResponse()`, and `textResponse()` methods to build a PSR-7-compliant response with the provided data and optional HTTP status code. Internally uses the Zend Diactoros library
- Added `ErrorHandler` class for request-level fallback (`set_error_handler` and `set_exception_handler`)
- Added basic endpoint skeleton generator: `bin/generate_endpoint [url]`
- Added traits for common endpoint behavior:
  - `Request\Get`
  - `Request\Post`
  - `Request\Put`
  - `Request\Delete`
  - `Input\NoRequired`
  - `Input\NoOptional`
  - `Authentication\None`
  - `Authentication\BearerToken`

### Changed
- Code is now tested automatically with Travis CI. PHP 7.0, 7.1, and 7.2 are supported
- [Zend-Diactoros](https://github.com/zendframework/zend-diactoros) is now included as a dependency. It is only used by the `ResponseBuilder` trait described above, but any PSR-7 library can be used
- Improved logging and error handling, with support for `PSR-3` loggers
- Improved validation of `.apiconfig`, displaying more useful errors to the user
- [**Breaking**] The container that's optionally injected into the Dispatcher is now expected to be PSR-11 compliant
- [**Breaking**] EndpointTestTrait moved to `Traits\EndpointTestCases`


### Deprecated
- Reorganized endpoint traits; old versions still work but will now emit a `E_USER_DEPRECATED` error when used. Their behavior is unchanged.
  - `GetRequest` => `Traits\Request\Get`
  - `PostRequest` => `Traits\Request\Post`
  - `PutRequest` => `Traits\Request\Put`
  - `DeleteRequest` => `Traits\Request\Delete`
  - `NoRequiredInputs` => `Traits\Input\NoRequired`
  - `NoOptionalInputs` => `Traits\Input\NoOptional`

### Internals
- Additional code quality tools have been added

## [2.3.1] - 2016-03-17
### Changed
- Reworked the auto-generated front controller

## [2.3.0] - 2016-03-13
### Added
- Added `Firehed\API\renderResponse()` which takes a complete PSR-7 response message and outputs the headers and body

## [2.2.2] - 2016-03-11
### Changed
- Fixed issue where exceptions thrown during error handling would result in unexpected behavior
- Fixed lingering dependency on InputObjects that slipped through

## [2.2.1] - 2016-03-11
### Changed
- `firehed/inputobjects` is now an optional, suggested package, and is no longer directly required. Projects using it must add it to their own Composer required section

## [2.2.0] - 2016-03-08
### Added
- Added traits for indicating an endpoint's HTTP method
- Added trait for indicating an endpoint has no required inputs
- Added trait for indicating an endpoint has no optional inputs

### Changed
- Updates to some dependencies

## [2.1.0] - 2016-03-07
### Added
- `Dispatcher->addResponseMiddleware()`: Allows providing post-execution handlers (for adding headers, etc)

### Changed
- Updates to some dependencies
- Dispatcher explicitly casts PSR-7 request body to string before

## [2.0.0] - 2015-10-21
Released for PHP 7
### Added
- `declare(strict_types=1)`
- return types
- Improved internal documentation

### Changed
- Updated PHPUnit version
- Updated PHP requirement in composer.json
- Updated input dependencies
- Most `catch` blocks handle `Throwable` where `Exception` was previously caught

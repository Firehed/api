# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- Added (and backfilled) this change log
- Added `ResponseBuilder` trait. It adds `emptyResponse()`, `htmlResponse()`, `jsonResponse()`, and `textResponse()` methods to build a PSR-7-compliant response with the provided data and optional HTTP status code. Internally uses the Zend Diactoros library
- New common use-case traits:

```
Firehed\API\Traits\
  Request\
    Get *
    Post *
    Put *
    Delete *
  Input\
    NoOptional *
    NoRequired *
  Authentication\
    None
    BearerToken

```

The traits marked with an asterisk (\*) replace their deprecated counterparts; see below

### Changed
- Code is now tested automatically with Travis CI. PHP 7.0, 7.1, and 7.2 are supported
- [Zend-Diactoros](https://github.com/zendframework/zend-diactoros) is now included as a dependency. It is only used by the `ResponseBuilder` trait described above, but any PSR-7 library can be used
- Improved logging and error handling, with support for `PSR-3` loggers
- [**Breaking**] The container that's optionally injected into the Dispatcher is now expected to be PSR-11 compliant


### Deprecated
The HTTP request method and No Input traits in the root `Traits` namespace are being deprecated in favor of the additions noted above. Using them will emit an `E_USER_DEPRECATED` error at runtime. Existing code should be migrated to using the above, which only requires changing the `use` statement. The behavior is otherwise identical

### Internals
Additional code quality tools have been added

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

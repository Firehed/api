# API Framework

[![Build Status](https://travis-ci.org/Firehed/api.svg?branch=master)](https://travis-ci.org/Firehed/api)



## Installation

API is available via composer:

`composer require firehed/api`

### Versions
The 2.0 line requires PHP7, taking advantage of new features like return types and scalar typehints. 1.0 is PHP5-compatible. All development will take place against the 2.0 line.

## Usage

Set up an `.apiconfig` file, which contains JSON-formatted settings for the
framework.

(document them)

Generate a default front-controller:

`./vendor/bin/generate_front_controller`

(apache/nginx settings)

After creating, modifying, or deleting endpoints, (re-)run the endpoint mapper:

`./vendor/bin/generate_endpoint_list`

If you have an automated build/deployment process, you should `gitignore` the generated files and run this script during that process. Otherwise you must run it manually and check in the changes.

## Testing

For convenience, a trait is included that includes tests for the description
methods of your endpoints. In your test case class (which typically extends
`PHPUnit_Framework_TestCase`, use the trait:

`use \Firehed\API\EndpointTestTrait`

And add a `getEndpoint` method that returns an instance of the endpoint under
test.

Be sure to add a `@coversDefaultClass` annotation to the test case
- `@covers` annotations are present in all of the trait tests for code coverage
reports.

### Example

    <?php

    namespace MyApp\API\Endpoints\User;

    use Firehed\API\EndpointTestTrait;

    /**
     * @coversDefaultClass MyApp\API\Endpoints\User\GetUserEndpoint
     * @covers ::<protected>
     * @covers ::<private>
     */
    class CreateTest extends \PHPUnit_Framework_TestCase
    {

        use EndpointTestTrait;

        protected function getEndpoint() {
            return new GetUserEndpoint();
        }
    }

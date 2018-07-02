<?php

declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Firehed\API\Enums\HTTPMethod;
use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Interfaces\ValidationInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Interface for implementing an endpoint in your application's API. All
 * classes in the project implementing this interface will be auto-detected at
 * build time (by `generate_endpoint_list`) and made available.
 *
 * If the implementing class has a constructor with parameters, the class MUST
 * be configuired with a container injected into the Dispatcher. If there are
 * no paramters on the constructor (or there is no constructor), that is
 * optional.
 *
 * In the next major version of this framework, this interface will no longer
 * extend HandlesOwnErrorsInterface; endpoints that actually have special error
 * handling logic must explictly implement that interface upon migrating to
 * that version.
 */
interface EndpointInterface extends ValidationInterface, HandlesOwnErrorsInterface
{

    /**
     * Execute the request. The validated input data will be provided as
     * an argument. This method WILL NOT be called if the input data fails to
     * validate according to the rules described in the input validation
     * methods, nor if the request URI and method do not match those indicated
     * by `getMethod()` and `getUri()` respectively.
     *
     * This method MUST return a PSR-7 formatted response object. It is
     * RECOMMENDED to handle all errors in `handleException()` rather than
     * catching and handling them in this method when possible.
     *
     * @param SafeInput $input the parsed and validated input
     * @return ResponseInterface
     */
    public function execute(SafeInput $input): ResponseInterface;

    /**
     * Indiate the request URI path that must be used for the inbound requests
     * to be routed to this endpoint. It MUST be an absolute path (leading /)
     * and MUST NOT include a trailing slash. A regular expression MAY be
     * provided; the delimeter is '#' so slashes do not need to be escaped.
     *
     * RegEx variables in the URI MAY BE captured using named subpatterns. E.g
     *
     *     /user/(?P<id>\d+)
     *
     * will capture 1 or more successive digits into the 'id' parameter. Note
     * that named subpatterns MUST be included in the parameters validated by
     * `getRequiredInputs()` or `getOptionalInputs()`. See
     * http://php.net/manual/en/function.preg-match.php#example-5349 for
     * additional information.
     *
     * @return string
     */
    public function getUri(): string;

    /**
     * Indiate the HTTP request method that must be used for inbound requests
     * to be routed to this endpoint.
     *
     * @return HTTPMethod
     */
    public function getMethod(): HTTPMethod;
}

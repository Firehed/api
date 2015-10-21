<?php

declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Throwable;
use Firehed\API\Enums\HTTPMethod;
use Firehed\Input\ {
    Containers\SafeInput,
    Interfaces\ValidationInterface
};
use Psr\Http\Message\ {
    RequestInterface as Request,
    ResponseInterface as Response
};

interface EndpointInterface extends ValidationInterface
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
     * @param SafeInput the parsed and validated input
     * @return ResponseInterface
     */
    public function execute(SafeInput $input): Response;

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

    /**
     * Authenticate the request. This method SHOULD copy any relevant
     * authentication information (user or application ID, etc) to local
     * properties, since the raw request will not be made available at any
     * other time. Additional processing MUST NOT be performed as this will be
     * called before even input validation. Logging and other metric gathering
     * MAY be performed during authentication if desired.
     *
     * This method SHOULD throw a `RuntimeException` upon failure (incorrect
     * credentials, etc), and MUST return `$this` when successful. An
     * implementation MAY choose to defer handling the failed authenticaton
     * until `::execute()`, although it is NOT RECOMMENDED.
     *
     * It is RECOMMENDED to implement this method in a trait, since most
     * Endpoints will share authentication logic.
     *
     * @param Request Inbound PSR-7 HTTP Request
     * @return self
     * @throws \RuntimeException if authentication fails
     */
    public function authenticate(Request $request): self;

    /**
     * Handle uncaught exceptions
     *
     * This method MUST accept any type of Exception and return a PSR-7
     * ResponseInterface object.
     *
     * It is RECOMMENDED to implement this method in a trait, since most
     * Endpoints will share error handling logic. In most cases, one trait per
     * supported MIME-type will probably suffice.
     *
     * @param Exception The uncaught exception
     * @return ResponseInterface The response to render
     */
    public function handleException(Throwable $e): Response;

}

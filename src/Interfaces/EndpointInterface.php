<?php

namespace Firehed\API\Interfaces;

use Firehed\Input\Containers\SafeInput;
use Firehed\Input\Interfaces\ValidationInterface;
use Psr\Http\Message\RequestInterface as Request;

interface EndpointInterface extends ValidationInterface
{

    /**
     * @param \Firehed\Input\Containers\SafeInput the parsed and validated
     * input
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function execute(SafeInput $input);

    /**
     * @return string
     */
    public function getUri();

    /**
     * @return \Firehed\API\Enums\HTTPMethod
     */
    public function getMethod();

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
    public function authenticate(Request $request);

}

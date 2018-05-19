<?php
declare(strict_types=1);

namespace Firehed\API\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Interface for Endpoints to indicate that they will handle their own
 * exceptions. Implementing this interface will be unnecessary in most cases,
 * preferring to let the application-wide exception handler to deal with common
 * errors. Note that this may receive exceptions before an endpoint's `execute`
 * method has been called (e.g. due to an input validation error), and as such
 * most not rely on any state established at that time (though may choose to
 * look for such state).
 */
interface HandlesOwnErrorsInterface
{
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
     * @param Throwable $e The uncaught exception
     * @return ResponseInterface The response to render
     */
    public function handleException(Throwable $e): ResponseInterface;
}

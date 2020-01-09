<?php
declare(strict_types=1);

namespace Firehed\API;

use ErrorException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * This will be deprecated in the next minor release and removed in the next
 * major release. Instead, prefer to provide an ErrorHandlerInterface to the
 * Dispatcher.
 */
class ErrorHandler
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleThrowable(Throwable $t): void
    {
        header('HTTP/1.1 500 Internal Server Error');
        $this->logger->error((string) $t);
    }

    /**
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public function handleError($severity, $message, $file, $line): void
    {
        if (error_reporting() & $severity) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
    }
}

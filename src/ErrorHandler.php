<?php
declare(strict_types=1);

namespace Firehed\API;

use ErrorException;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleThrowable(Throwable $t)
    {
        header('HTTP/1.1 500 Internal Server Error');
        $this->logger->error((string) $t);
    }

    public function handleError($severity, $message, $file, $line)
    {
        if (error_reporting() & $severity) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
    }
}
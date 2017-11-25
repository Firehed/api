<?php
declare(strict_types=1);

namespace Firehed\API;

use ErrorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

class ErrorHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function handleThrowable(Throwable $t)
    {
        header('HTTP/1.1 500 Internal Server Error');
        if ($this->logger) {
            $this->logger->error((string) $t);
        }
    }

    public function handleError($severity, $message, $file, $line)
    {
        if (error_reporting() & $severity) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
    }
}

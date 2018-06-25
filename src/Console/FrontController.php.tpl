<?php

declare(strict_types=1);

/**
 * This file automatically @generated by bin/api api:generateFrontController
 */
use Firehed\API\Dispatcher;
use Firehed\API\ErrorHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zend\Diactoros\ServerRequestFactory;

// Set CWD to repo root
chdir(__DIR__.'%s');

// Composer autoloader
require 'vendor/autoload.php';

$config = %s;
if ($config && $config->has(LoggerInterface::class)) {
    $logger = $config->get(LoggerInterface::class);
} else {
    $logger = new NullLogger();
}

$handler = new ErrorHandler($logger);
// Convert all levels of PHP errors to ErrorExceptions
set_error_handler([$handler, 'handleError'], -1);

// Handle the 'error handler threw an exception' case
set_exception_handler([$handler, 'handleThrowable']);


$response = (new Dispatcher())
    ->setContainer($config)
    ->setEndpointList('__endpoint_list__.json')
    ->setParserList('__parser_list__.json')
    ->setRequest(ServerRequestFactory::fromGlobals())
    // ->addResponseMiddleware(function(){}) ...
    ->dispatch();

Firehed\API\ResponseRenderer::render($response);

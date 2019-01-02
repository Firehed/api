<?php
declare(strict_types=1);

use Firehed\API\Config;
use Psr\Container\ContainerInterface;

$root = __DIR__;
while (!file_exists($root.'/vendor/autoload.php') && $root != DIRECTORY_SEPARATOR) {
    $root = dirname($root);
}

chdir($root);

require_once 'vendor/autoload.php';

return Config::load(Config::FILENAME);

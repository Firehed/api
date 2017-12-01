<?php
declare(strict_types=1);

use SimpleLogger\Stderr;

$root = __DIR__;
while (!file_exists($root.'/vendor/autoload.php') && $root != DIRECTORY_SEPARATOR) {
    $root = dirname($root);
}

require $root.'/vendor/autoload.php';

$stderr = new Stderr();

$file = '/.apiconfig';
$config_file = $root.$file;

if (!file_exists($config_file) || !is_readable($config_file)) {
    $stderr->error(".apiconfig file not found");
    exit(1);
}

$config = json_decode(file_get_contents($config_file), true);

if (JSON_ERROR_NONE !== json_last_error()) {
    $stderr->error(".apiconfig contains invalid JSON");
    exit(1);
}

$config = array_map(function ($val) {
    return rtrim($val, '/');
}, $config);

$required_keys = [
    'webroot',
    'namespace',
    'source',
];
$optionalKeys = [
    'container',
];

$allKeys = array_merge($required_keys, $optionalKeys);

$keysInConfig = array_keys($config);

if ($diff = array_diff($keysInConfig, $allKeys)) {
    $stderr->error(sprintf(
        'Found unexpected config keys in .apiconfig: %s',
        implode(', ', $diff)
    ));
    exit(1);
}

foreach ($required_keys as $required_key) {
    if (!array_key_exists($required_key, $config)) {
        $stderr->error(".apiconfig is missing value for '$required_key'");
        exit(1);
    }
}

if (array_key_exists('container', $config)) {
    $file = $config['container'];
    if (!file_exists($file)) {
        $stderr->error(".apiconfig[container] must point to a file returning a PSR-11 container");
        exit(1);
    }
    $container = require $config['container'];
    if (!$container instanceof Psr\Container\ContainerInterface) {
        $stderr->error(".apiconfig[container] must point to a file returning a PSR-11 container");
        exit(1);
    }
}

$config['local_project_root'] = dirname($config_file);

return $config;

<?php

$file = '/.apiconfig';
$dir = __DIR__;

// Walk up the directory structure to find the file
while (!file_exists($dir.$file) && $dir != '/') {
    $dir = dirname($dir);
}
$config_file = $dir.$file;

if (!file_exists($config_file) || !is_readable($config_file)) {
    fwrite(STDERR, ".apiconfig file not found\n");
    exit(1);
}
$config = json_decode(file_get_contents($config_file), true);
if (JSON_ERROR_NONE !== json_last_error()) {
    fwrite(STDERR, ".apiconfig contains invalid JSON\n");
    exit(1);
}

$required_keys = [
    'webroot',
    'namespace',
    'source',
];

foreach ($required_keys as $required_key) {
    if (!array_key_exists($required_key, $config)) {
        fwrite(STDERR, ".apiconfig is missing value for '$required_key'\n");
        exit(1);
    }
}

$config['local_project_root'] = dirname($config_file);

return $config;

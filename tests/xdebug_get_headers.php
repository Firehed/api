<?php
declare(strict_types=1);

// This is a compatibiltiy hack for test environments

if (!function_exists('xdebug_get_headers')) {
    function xdebug_get_headers()
    {
        return [];
    }
}

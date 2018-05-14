<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class Config implements ContainerInterface
{
    const KEY_WEBROOT = 'webroot';
    const KEY_NAMESPACE = 'namespace';
    const KEY_SOURCE = 'source';
    const KEY_CONTAINER = 'container';

    const OPTIONAL_PARAMS = [
        self::KEY_CONTAINER,
    ];

    const REQUIRED_PARAMS = [
        self::KEY_WEBROOT,
        self::KEY_NAMESPACE,
        self::KEY_SOURCE,
    ];

    private $data = [];

    public function __construct(array $params)
    {
        foreach (self::REQUIRED_PARAMS as $param) {
            if (array_key_exists($param, $params)) {
                $this->data[$param] = $params[$param];
            } else {
                throw new RuntimeException(sprintf('Config missing required key "%s"', $param));
            }
        }
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new class("$id not in container") extends RuntimeException
                implements NotFoundExceptionInterface {
            };
        }
        return $this->data[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->data);
    }

    public static function load(string $file): Config
    {
        if (!file_exists($file)) {
            throw new RuntimeException('Config file not found');
        }
        if (!is_readable($file)) {
            throw new RuntimeException('Config file not readable');
        }
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Config file contained invalid JSON');
        }

        return new Config($data);
    }
}

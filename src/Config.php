<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use TypeError;

/**
 * @internal
 */
class Config implements ContainerInterface
{
    const FILENAME = '.apiconfig';

    const KEY_CONTAINER = 'container';
    const KEY_NAMESPACE = 'namespace';
    const KEY_SOURCE = 'source';
    const KEY_WEBROOT = 'webroot';

    const OPTIONAL_PARAMS = [
        self::KEY_CONTAINER,
    ];

    const REQUIRED_PARAMS = [
        self::KEY_WEBROOT,
        self::KEY_NAMESPACE,
        self::KEY_SOURCE,
    ];

    /** @var array<string, string> */
    private $data = [];

    /**
     * @param array<string, string> $params
     */
    public function __construct(array $params)
    {
        foreach (self::REQUIRED_PARAMS as $param) {
            if (array_key_exists($param, $params)) {
                $this->data[$param] = $params[$param];
            } else {
                throw new RuntimeException(sprintf('Config missing required key "%s"', $param));
            }
        }

        foreach (self::OPTIONAL_PARAMS as $param) {
            if (array_key_exists($param, $params)) {
                $this->data[$param] = $params[$param];
            }
        }

        $this->validateContainer();
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

    public function has($id): bool
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
        assert($json !== false);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Config file contained invalid JSON');
        }

        return new Config($data);
    }

    private function validateContainer(): void
    {
        if (!$this->has(self::KEY_CONTAINER)) {
            return;
        }
        $containerFile = $this->get(self::KEY_CONTAINER);
        $loader = function (string $path): ContainerInterface {
            if (!file_exists($path)) {
                throw new RuntimeException('Container file not found');
            }
            return require $path;
        };
        try {
            $loader($containerFile);
        } catch (TypeError $e) {
            throw new RuntimeException('The configured container did not return a PSR-11 container');
        }
    }
}

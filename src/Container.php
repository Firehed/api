<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container as Psr;

/**
 * Ultra simple array wrapper for a PSR container. No closures, no evaluation,
 * nothing else. Just a dumb-as-rocks key/value store.
 */
class Container implements Psr\ContainerInterface
{
    /** @var array<string, mixed> */
    private $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->data);
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new class extends \Exception implements Psr\NotFoundExceptionInterface
            {
            };
        }
        return $this->data[$id];
    }
}

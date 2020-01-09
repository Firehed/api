<?php

// Config fixture for unit tests
use Psr\Container\ContainerInterface;

return new class implements ContainerInterface
{
    public function get($id)
    {
    }

    public function has($id)
    {
        return false;
    }
};

<?php

declare(strict_types=1);

namespace MezzioTest\Authentication\Session\TestAsset;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;

final class InMemoryContainerPSR11V2 implements ContainerInterface
{
    /** @var array<string,mixed> */
    private array $services = [];

    /**
     * @return mixed
     */
    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new class ($id . ' was not found') extends RuntimeException implements NotFoundExceptionInterface {
            };
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    /** @param mixed $item */
    public function set(string $id, $item): void
    {
        $this->services[$id] = $item;
    }
}

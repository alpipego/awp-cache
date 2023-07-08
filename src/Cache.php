<?php

declare(strict_types=1);

namespace Alpipego\AWP\Cache;

use Exception;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Cache
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key)
    {
        try {
            return $this->cache->get($key, static function () {
                throw new Exception();
            });
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            $this->cache->get($key, static function () {
                throw new Exception();
            });
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function set(string $key, string $value)
    {
        return $this->cache->get($key, static function (ItemInterface $item) use ($value) {
            $item->tag(['all_items']);
            return $value;
        });
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->invalidateTags(['all_items']);
    }
}

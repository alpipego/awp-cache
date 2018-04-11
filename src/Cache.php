<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 03.04.18
 * Time: 15:16
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Cache;

use Symfony\Component\Cache\Simple\AbstractCache;

class Cache
{
    private $cache;

    public function __construct(AbstractCache $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key, $default = null)
    {
        return $this->cache->get($key, $default);
    }

    public function has(string $key) : bool
    {
        return $this->cache->has($key);
    }

    public function set(string $key, $value, $ttl = null) : bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    public function delete(string $key) : bool
    {
        return $this->cache->delete($key);
    }

    public function clear() : bool
    {
        return $this->cache->clear();
    }
}

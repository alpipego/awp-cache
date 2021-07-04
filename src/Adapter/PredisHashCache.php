<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 04.04.18
 * Time: 09:09
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Cache\Adapter;

use Predis\Client;
use Symfony\Component\Cache\Simple\AbstractCache;

class PredisHashCache extends AbstractCache
{
    private $namespace;
    private $redis;

    public function __construct(Client $redisClient, string $namespace, int $defaultLifetime = 0)
    {
        $this->redis     = $redisClient;
        $this->namespace = empty($namespace) ? '_' : $namespace;
        parent::__construct($namespace, $defaultLifetime);
    }

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|\Traversable The corresponding values found in the cache
     */
    protected function doFetch(array $ids)
    {
        if ( ! $ids) {
            return [];
        }

        return $this->redis->hmget($this->namespace, $this->keys($ids));
    }

    private function keys(array $dictionary) : array
    {
        foreach ($dictionary as $key => $item) {
            if ( ! is_string($key)) {
                $dictionary[] = $this->key($item);
                unset($dictionary[$key]);
            } else {
                $newkey              = $this->key($key);
                $dictionary[$newkey] = $item;
                unset($dictionary[$key]);
            }
        }

        return $dictionary;
    }

    private function key(string $key) : string
    {
        $keyArr = explode(':', $key);

        return end($keyArr);
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    protected function doHave($id)
    {
        return (bool)$this->redis->hexists($this->namespace, $this->key($id));
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear($namespace)
    {
        return (bool)$this->redis->del(str_replace(static::NS_SEPARATOR, '', $namespace));
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    protected function doDelete(array $ids)
    {
        $this->redis->hdel($this->namespace, $this->keys($ids));
    }

    /**
     * Persists several cache items immediately.
     *
     * @param array $values The values to cache, indexed by their cache identifier
     * @param int $lifetime The lifetime of the cached values, 0 for persisting until manual cleaning
     *
     * @return array|bool The identifiers that failed to be cached or a boolean stating if caching succeeded or not
     */
    protected function doSave(array $values, $lifetime)
    {
        $this->redis->hmset($this->namespace, $this->keys($values));

        //        $this->redis->expire($this->path, $lifetime);

        return true;
    }
}

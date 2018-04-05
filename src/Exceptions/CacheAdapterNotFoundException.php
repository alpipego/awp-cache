<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 03.04.18
 * Time: 14:37
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Cache\Exceptions;

use Psr\SimpleCache\CacheException;

class CacheAdapterNotFoundException extends \InvalidArgumentException implements CacheException
{

    /**
     * CacheAdapterNotFoundException constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->message = sprintf('%s is not a valid cache adapter', $type);
    }
}

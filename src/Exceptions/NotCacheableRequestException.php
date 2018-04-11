<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 03.04.18
 * Time: 15:06
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Cache\Exceptions;

use Psr\SimpleCache\CacheException;

class NotCacheableRequestException extends \Exception implements CacheException
{

}

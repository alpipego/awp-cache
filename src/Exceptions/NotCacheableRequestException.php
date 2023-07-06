<?php

declare(strict_types=1);

namespace Alpipego\AWP\Cache\Exceptions;

use Psr\Cache\CacheException;

class NotCacheableRequestException extends \Exception implements CacheException
{

}

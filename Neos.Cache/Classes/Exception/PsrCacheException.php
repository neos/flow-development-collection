<?php
namespace Neos\Cache\Exception;

use Psr\Cache\CacheException as Psr6CacheException;
use Psr\SimpleCache\CacheException as Psr16CacheException;

/**
 *
 */
class PsrCacheException extends \Exception implements Psr6CacheException, Psr16CacheException
{
}

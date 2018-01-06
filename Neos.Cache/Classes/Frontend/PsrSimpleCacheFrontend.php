<?php
namespace Neos\Cache\Frontend;

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\DateInterval;

/**
 *
 */
class PsrSimpleCacheFrontend implements CacheInterface
{
    /**
     * Pattern an entry identifier must match.
     */
    const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_\.]{1,64}$/';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var BackendInterface
     */
    protected $backend;

    /**
     * Constructs the cache
     *
     * @param string $identifier A identifier which describes this cache
     * @param BackendInterface $backend Backend to be used for this cache
     * @throws Exception\PsrInvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
     */
    public function __construct(string $identifier, BackendInterface $backend)
    {
        if ($this->isValidEntryIdentifier($identifier) === false) {
            throw new Exception\PsrInvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1515192811703);
        }
        $this->identifier = $identifier;
        $this->backend = $backend;
    }

    /**
     * Saves the value of a PHP variable in the cache. Note that the variable
     * will be serialized if necessary.
     *
     * @param string $key An identifier used for this cache entry
     * @param mixed $variable The variable to cache
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return bool
     *
     * @throws Exception
     * @throws Exception\PsrInvalidArgumentException
     */
    public function set($key, $variable, $lifetime = null)
    {
        $this->ensureValidEntryIdentifier($key);
        try {
            $this->backend->set($key, serialize($variable), [], $lifetime);
        } catch (\Throwable $throwable) {
            throw new Exception('An exception was thrown in retrieving the key from the cache backend.', 1515193492062, $throwable);
        }
        return true;
    }

    /**
     * Finds and returns a variable value from the cache.
     *
     * @param string $key Identifier of the cache entry to fetch
     * @param mixed $defaultValue
     * @return mixed The value or the defaultValue if entry was not found
     * @throws Exception
     * @throws Exception\PsrInvalidArgumentException
     */
    public function get($key, $defaultValue = null)
    {
        $this->ensureValidEntryIdentifier($key);
        try {
            $rawResult = $this->backend->get($key);
        } catch (\Throwable $throwable) {
            throw new Exception('An exception was thrown in retrieving the key from the cache backend.', 1515193339722, $throwable);
        }
        if ($rawResult === false) {
            return $defaultValue;
        }

        return unserialize($rawResult);
    }

    /**
     * @param string $key
     * @return bool
     * @throws Exception
     * @throws Exception\PsrInvalidArgumentException
     */
    public function delete($key)
    {
        $this->ensureValidEntryIdentifier($key);
        try {
            return $this->backend->remove($key);
        } catch (\Throwable $throwable) {
            throw new Exception('An exception was thrown in removing the key from the cache backend.', 1515193384076, $throwable);
        }
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $this->backend->flush();
        return true;
    }

    /**
     * @param iterable $keys
     * @param mixed $default
     * @return iterable
     * @throws Exception\PsrInvalidArgumentException
     * @throws Exception
     */
    public function getMultiple($keys, $default = null)
    {
        return array_map(function ($key) use ($default) {
            return $this->get($key, $default);
        }, $keys);
    }

    /**
     * @param iterable $values
     * @param integer $ttl
     * @return bool
     * @throws Exception
     * @throws Exception\PsrInvalidArgumentException
     */
    public function setMultiple($values, $ttl = null)
    {
        $allSet = true;
        foreach ($values as $key => $value) {
            $allSet = $this->set($key, $value, $ttl) && $allSet;
        }

        return $allSet;
    }

    /**
     * @param iterable $keys
     * @return bool
     * @throws Exception\PsrInvalidArgumentException
     */
    public function deleteMultiple($keys)
    {
        array_map(function ($key) {
            $this->delete($key);
        }, $keys);

        return true;
    }

    /**
     * @param string $key
     * @return bool
     * @throws Exception\PsrInvalidArgumentException
     */
    public function has($key)
    {
        $this->ensureValidEntryIdentifier($key);
        return $this->backend->has($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function isValidEntryIdentifier(string $key)
    {
        return (preg_match(self::PATTERN_ENTRYIDENTIFIER, $key) === 1);
    }

    /**
     * @param $key
     * @throws Exception\PsrInvalidArgumentException
     */
    protected function ensureValidEntryIdentifier($key)
    {
        if ($this->isValidEntryIdentifier($key) === false) {
            throw new Exception\PsrInvalidArgumentException('"' . $key . '" is not a valid cache key.', 1515192768083);
        }
    }
}

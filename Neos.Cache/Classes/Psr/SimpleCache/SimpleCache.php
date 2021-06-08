<?php
declare(strict_types=1);

namespace Neos\Cache\Psr\SimpleCache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception;
use Neos\Cache\Psr\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * A simple cache frontend
 * Note: This does not follow the \Neos\Cache\Frontend\FrontendInterface this package provides.
 */
class SimpleCache implements CacheInterface
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
     * @throws InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
     */
    public function __construct(string $identifier, BackendInterface $backend)
    {
        if ($this->isValidEntryIdentifier($identifier) === false) {
            throw new InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1515192811703);
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
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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

        return unserialize((string)$rawResult);
    }

    /**
     * @param string $key
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @param iterable $values
     * @param integer $ttl
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        };

        return true;
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
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
    protected function isValidEntryIdentifier(string $key): bool
    {
        return (preg_match(self::PATTERN_ENTRYIDENTIFIER, $key) === 1);
    }

    /**
     * @param string $key
     * @return void
     * @throws InvalidArgumentException
     */
    protected function ensureValidEntryIdentifier($key): void
    {
        if ($this->isValidEntryIdentifier($key) === false) {
            throw new InvalidArgumentException('"' . $key . '" is not a valid cache key.', 1515192768083);
        }
    }
}

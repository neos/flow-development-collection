<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\AbstractBackend as IndependentAbstractBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception as CacheException;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;

/**
 * A caching backend which stores cache entries in Redis using the phpredis PHP extension.
 * Redis is a noSQL database with very good scaling characteristics
 * in proportion to the amount of entries and data size.
 *
 * @see http://redis.io/
 * @see https://github.com/nicolasff/phpredis
 *
 * Available backend options:
 *  - defaultLifetime: The default lifetime of a cache entry
 *  - hostname:        The hostname (or socket filepath) of the redis server
 *  - port:            The TCP port of the redis server (will be ignored if connecting to a socket)
 *  - database:        The database index that will be used. By default,
 *                     Redis has 16 databases with index number 0 - 15
 *  - password:        The password needed for redis clients to connect to the server (hostname)
 *
 * Requirements:
 *  - Redis 2.6.0+ (tested with 2.6.14 and 2.8.5)
 *  - phpredis with Redis 2.6 support, e.g. 2.2.4 (tested with 92782639b0329ff91658a0602a3d816446a3663d from 2014-01-06)
 *
 * Implementation based on ext:rediscache by Christopher Hlubek - networkteam GmbH
 *
 * Each Redis key contains a prefix built from the cache identifier,
 * so one single database can be used for different caches.
 *
 * Cache entry data is stored in a simple key. Tags are stored in Sets.
 * Since Redis < 2.8.0 does not provide a mechanism for iterating over keys,
 * a separate list with all entries is populated
 *
 * @api
 */
class RedisBackend extends IndependentAbstractBackend implements TaggableBackendInterface, IterableBackendInterface, FreezableBackendInterface, PhpCapableBackendInterface, WithStatusInterface
{
    use RequireOnceFromValueTrait;

    const MIN_REDIS_VERSION = '2.6.0';

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var integer Cursor used for iterating over cache entries
     */
    protected $entryCursor = 0;

    /**
     * @var boolean|null
     */
    protected $frozen;

    /**
     * @var string
     */
    protected $hostname = '127.0.0.1';

    /**
     * @var integer
     */
    protected $port = 6379;

    /**
     * @var integer
     */
    protected $database = 0;

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var integer
     */
    protected $compressionLevel = 0;

    /**
     * Constructs this backend
     *
     * @param EnvironmentConfiguration $environmentConfiguration
     * @param array $options Configuration options - depends on the actual backend
     * @throws CacheException
     */
    public function __construct(EnvironmentConfiguration $environmentConfiguration, array $options)
    {
        parent::__construct($environmentConfiguration, $options);
        if (!$this->redis instanceof \Redis) {
            $this->redis = $this->getRedisClient();
        }
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \RuntimeException
     * @throws CacheException
     * @return void
     * @api
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
        if ($this->isFrozen()) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }

        if ($lifetime === null) {
            $lifetime = $this->defaultLifetime;
        }

        $setOptions = [];
        if ($lifetime > 0) {
            $setOptions['ex'] = $lifetime;
        }

        $this->redis->multi();
        $result = $this->redis->set($this->buildKey('entry:' . $entryIdentifier), $this->compress($data), $setOptions);
        if ($result === false) {
            $this->verifyRedisVersionIsSupported();
        }
        $this->redis->lRem($this->buildKey('entries'), $entryIdentifier, 0);
        $this->redis->rPush($this->buildKey('entries'), $entryIdentifier);
        foreach ($tags as $tag) {
            $this->redis->sAdd($this->buildKey('tag:' . $tag), $entryIdentifier);
            $this->redis->sAdd($this->buildKey('tags:' . $entryIdentifier), $tag);
        }
        $this->redis->exec();
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or false if the cache entry could not be loaded
     * @api
     */
    public function get(string $entryIdentifier)
    {
        return $this->uncompress($this->redis->get($this->buildKey('entry:' . $entryIdentifier)));
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     * @api
     */
    public function has(string $entryIdentifier): bool
    {
        // exists returned true or false in phpredis versions < 4.0.0, now it returns the number of keys
        return (bool)$this->redis->exists($this->buildKey('entry:' . $entryIdentifier));
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @throws \RuntimeException
     * @return boolean true if (at least) an entry could be removed or false if no entry was found
     * @api
     */
    public function remove(string $entryIdentifier): bool
    {
        if ($this->isFrozen()) {
            throw new \RuntimeException(sprintf('Cannot remove cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }
        do {
            $tagsKey = $this->buildKey('tags:' . $entryIdentifier);
            $this->redis->watch($tagsKey);
            $tags = $this->redis->sMembers($tagsKey);
            $this->redis->multi();
            $this->redis->del($this->buildKey('entry:' . $entryIdentifier));
            foreach ($tags as $tag) {
                $this->redis->sRem($this->buildKey('tag:' . $tag), $entryIdentifier);
            }
            $this->redis->del($this->buildKey('tags:' . $entryIdentifier));
            $this->redis->lRem($this->buildKey('entries'), $entryIdentifier, 0);
            /** @var array|bool $result */
            $result = $this->redis->exec();
        } while ($result === false);

        return true;
    }

    /**
     * Removes all cache entries of this cache
     *
     * The flush method will use the EVAL command to flush all entries and tags for this cache
     * in an atomic way.
     *
     * @throws \RuntimeException
     * @return void
     * @api
     */
    public function flush(): void
    {
        $script = "
		local entries = redis.call('LRANGE',KEYS[1],0,-1)
		for k1,entryIdentifier in ipairs(entries) do
			redis.call('DEL', ARGV[1]..'entry:'..entryIdentifier)
			local tags = redis.call('SMEMBERS', ARGV[1]..'tags:'..entryIdentifier)
			for k2,tagName in ipairs(tags) do
				redis.call('DEL', ARGV[1]..'tag:'..tagName)
			end
			redis.call('DEL', ARGV[1]..'tags:'..entryIdentifier)
		end
		redis.call('DEL', KEYS[1])
		redis.call('DEL', KEYS[2])
		";
        $this->redis->eval($script, [$this->buildKey('entries'), $this->buildKey('frozen'), $this->buildKey('')], 2);

        $this->frozen = null;
    }

    /**
     * This backend does not need an externally triggered garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage(): void
    {
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function buildKey(string $identifier): string
    {
        return $this->cacheIdentifier . ':' . $identifier;
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @throws \RuntimeException
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTag(string $tag): int
    {
        if ($this->isFrozen()) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }

        $script = "
		local entries = redis.call('SMEMBERS', KEYS[1])
		for k1,entryIdentifier in ipairs(entries) do
			redis.call('DEL', ARGV[1]..'entry:'..entryIdentifier)
			local tags = redis.call('SMEMBERS', ARGV[1]..'tags:'..entryIdentifier)
			for k2,tagName in ipairs(tags) do
				redis.call('SREM', ARGV[1]..'tag:'..tagName, entryIdentifier)
			end
			redis.call('DEL', ARGV[1]..'tags:'..entryIdentifier)
			redis.call('LREM', KEYS[2], 0, entryIdentifier)
		end
		return #entries
		";
        return $this->redis->eval($script, [$this->buildKey('tag:' . $tag), $this->buildKey('entries'), $this->buildKey('')], 2);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return string[] An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag(string $tag): array
    {
        return $this->redis->sMembers($this->buildKey('tag:' . $tag));
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->get($this->key());
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->entryCursor++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $entryIdentifier = $this->redis->lIndex($this->buildKey('entries'), $this->entryCursor);
        if ($entryIdentifier !== false && !$this->has($entryIdentifier)) {
            return false;
        }
        return $entryIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->key() !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->entryCursor = 0;
    }

    /**
     * Freezes this cache backend.
     *
     * All data in a frozen backend remains unchanged and methods which try to add
     * or modify data result in an exception thrown. Possible expiry times of
     * individual cache entries are ignored.
     *
     * A frozen backend can only be thawn by calling the flush() method.
     *
     * @throws \RuntimeException
     * @return void
     */
    public function freeze(): void
    {
        if ($this->isFrozen()) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }
        do {
            $entriesKey = $this->buildKey('entries');
            $this->redis->watch($entriesKey);
            $entries = $this->redis->lRange($entriesKey, 0, -1);
            $this->redis->multi();
            foreach ($entries as $entryIdentifier) {
                $this->redis->persist($this->buildKey('entry:' . $entryIdentifier));
            }
            $this->redis->set($this->buildKey('frozen'), '1');
            /** @var array|bool $result */
            $result = $this->redis->exec();
        } while ($result === false);
        $this->frozen = true;
    }

    /**
     * Tells if this backend is frozen.
     *
     * @return boolean
     */
    public function isFrozen(): bool
    {
        if (null === $this->frozen) {
            $this->frozen = (bool)$this->redis->exists($this->buildKey('frozen'));
        }

        return $this->frozen;
    }

    /**
     * Sets the default lifetime for this cache backend
     *
     * @param integer $lifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. 0 means unlimited lifetime.
     * @return void
     * @api
     */
    public function setDefaultLifetime(int $lifetime): void
    {
        $this->defaultLifetime = $lifetime;
    }

    /**
     * Sets the hostname or the socket of the Redis server
     *
     * @param string $hostname Hostname of the Redis server
     * @api
     */
    public function setHostname(string $hostname): void
    {
        $this->hostname = $hostname;
    }

    /**
     * Sets the port of the Redis server.
     *
     * Leave this empty if you want to connect to a socket
     *
     * @param integer $port Port of the Redis server
     * @api
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * Sets the database that will be used for this backend
     *
     * @param integer $database Database that will be used
     * @api
     */
    public function setDatabase(int $database): void
    {
        $this->database = $database;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @param integer $compressionLevel
     */
    public function setCompressionLevel(int $compressionLevel): void
    {
        $this->compressionLevel = $compressionLevel;
    }

    /**
     * @param \Redis $redis
     * @return void
     */
    public function setRedis(\Redis $redis = null): void
    {
        if ($redis !== null) {
            $this->redis = $redis;
        }
    }

    /**
     * @param string|bool $value
     * @return string|bool
     */
    private function uncompress($value)
    {
        if ($value === false || empty($value)) {
            return $value;
        }
        return $this->useCompression() ? gzdecode((string) $value) : $value;
    }

    /**
     * @param string $value
     * @return string
     */
    private function compress(string $value): string
    {
        return $this->useCompression() ? gzencode($value, $this->compressionLevel) : $value;
    }

    /**
     * @return boolean
     */
    private function useCompression(): bool
    {
        return $this->compressionLevel > 0;
    }

    /**
     * @return \Redis
     * @throws CacheException
     */
    private function getRedisClient(): \Redis
    {
        $redis = new \Redis();

        try {
            $connected = false;
            // keep the assignment above! the connect calls below leaves the variable undefined, if an error occurs.
            if (strpos($this->hostname, '/') !== false) {
                $connected = $redis->connect($this->hostname);
            } else {
                $connected = $redis->connect($this->hostname, $this->port);
            }
        } finally {
            if ($connected === false) {
                throw new CacheException('Could not connect to Redis.', 1391972021);
            }
        }

        if ($this->password !== '') {
            if (!$redis->auth($this->password)) {
                throw new CacheException('Redis authentication failed.', 1502366200);
            }
        }
        $redis->select($this->database);
        return $redis;
    }

    /**
     * @return void
     * @throws CacheException
     */
    protected function verifyRedisVersionIsSupported(): void
    {
        // Redis client could be in multi mode, discard for checking the version
        $this->redis->discard();

        $serverInfo = (array)$this->redis->info('SERVER');
        if (!isset($serverInfo['redis_version'])) {
            throw new CacheException('Unsupported Redis version, the Redis cache backend needs at least version ' . self::MIN_REDIS_VERSION, 1438251553);
        }
        if (version_compare($serverInfo['redis_version'], self::MIN_REDIS_VERSION) < 0) {
            throw new CacheException('Redis version ' . $serverInfo['redis_version'] . ' not supported, the Redis cache backend needs at least version ' . self::MIN_REDIS_VERSION, 1438251628);
        }
    }

    /**
     * Validates that the configured redis backend is accessible and returns some details about its configuration if that's the case
     *
     * @return Result
     * @api
     */
    public function getStatus(): Result
    {
        $result = new Result();
        try {
            $this->verifyRedisVersionIsSupported();
        } catch (CacheException $exception) {
            $result->addError(new Error($exception->getMessage(), (int)$exception->getCode(), [], 'Redis Version'));
            return $result;
        }
        $serverInfo = (array)$this->redis->info('SERVER');
        if (isset($serverInfo['redis_version'])) {
            $result->addNotice(new Notice((string)$serverInfo['redis_version'], null, [], 'Redis version'));
        }
        if (isset($serverInfo['tcp_port'])) {
            $result->addNotice(new Notice((string)$serverInfo['tcp_port'], null, [], 'TCP Port'));
        }
        if (isset($serverInfo['uptime_in_seconds'])) {
            $result->addNotice(new Notice((string)$serverInfo['uptime_in_seconds'], null, [], 'Uptime (seconds)'));
        }
        return $result;
    }
}

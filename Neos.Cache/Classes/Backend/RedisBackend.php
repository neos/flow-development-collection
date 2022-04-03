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
 * @see https://github.com/phpredis/phpredis
 *
 * Available backend options:
 *  - defaultLifetime: The default lifetime of a cache entry
 *  - hostname:        The hostname (or socket filepath) of the redis server
 *  - port:            The TCP port of the redis server (will be ignored if connecting to a socket)
 *  - database:        The database index that will be used. By default,
 *                     Redis has 16 databases with index number 0 - 15
 *  - password:        The password needed for redis clients to connect to the server (hostname)
 *  - batchSize:       Maximum number of parameters per query for batch operations
 *
 * Requirements:
 *  - Redis 6.0.0+
 *  - phpredis with Redis 6.0 support
 *
 * Implementation based on ext:rediscache by Christopher Hlubek - networkteam GmbH
 *
 * Each Redis key contains a prefix built from the cache identifier,
 * so one single database can be used for different caches.
 *
 * Cache entry data is stored in a simple key. Tags are stored in Sets.
 *
 * @api
 */
class RedisBackend extends IndependentAbstractBackend implements TaggableBackendInterface, IterableBackendInterface, FreezableBackendInterface, PhpCapableBackendInterface, WithStatusInterface
{
    use RequireOnceFromValueTrait;

    public const MIN_REDIS_VERSION = '6.0.0';

    /**
     * @var \Redis
     */
    protected $redis;

    protected ?bool $frozen = null;

    protected string $hostname = '127.0.0.1';

    protected int $port = 6379;

    protected int $database = 0;

    protected string $password = '';

    protected int $compressionLevel = 0;

    /**
     * Redis allows a maximum of 1024 * 1024 parameters, but we use a lower limit to prevent long blocking calls.
     */
    protected int $batchSize = 100000;

    /**
     * @var \ArrayIterator|null
     */
    private $entryIterator;

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
     * @param integer|null $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \RuntimeException
     * @throws CacheException
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

        $redisTags = array_reduce($tags, function ($redisTags, $tag) use ($lifetime, $entryIdentifier) {
            $expire = $this->calculateExpires($this->getPrefixedIdentifier('tag:' . $tag), $lifetime);
            $redisTags[] = ['key' => $this->getPrefixedIdentifier('tag:' . $tag), 'value' => $entryIdentifier, 'expire' => $expire];

            $expire = $this->calculateExpires($this->getPrefixedIdentifier('tags:' . $entryIdentifier), $lifetime);
            $redisTags[] = ['key' => $this->getPrefixedIdentifier('tags:' . $entryIdentifier), 'value' => $tag, 'expire' => $expire];
            return $redisTags;
        }, []);

        $this->redis->multi();
        $result = $this->redis->set($this->getPrefixedIdentifier('entry:' . $entryIdentifier), $this->compress($data), $setOptions);
        if (!$result instanceof \Redis) {
            $this->verifyRedisVersionIsSupported();
        }
        foreach ($redisTags as $tag) {
            $this->redis->sAdd($tag['key'], $tag['value']);
            if ($tag['expire'] > 0) {
                $this->redis->expire($tag['key'], $tag['expire']);
            } else {
                $this->redis->persist($tag['key']);
            }
        }
        $this->redis->exec();
    }

    /**
     * Calculate the max lifetime for a tag
     */
    private function calculateExpires(string $tag, int $lifetime): int
    {
        $ttl = (int)$this->redis->ttl($tag);
        if ($ttl < 0 || $lifetime === self::UNLIMITED_LIFETIME) {
            return -1;
        }
        return max($ttl, $lifetime);
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return bool|string The cache entry's content as a string or false if the cache entry could not be loaded
     * @api
     */
    public function get(string $entryIdentifier): string|bool
    {
        return $this->uncompress($this->redis->get($this->getPrefixedIdentifier('entry:' . $entryIdentifier)));
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
        return (bool)$this->redis->exists($this->getPrefixedIdentifier('entry:' . $entryIdentifier));
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
            $tagsKey = $this->getPrefixedIdentifier('tags:' . $entryIdentifier);
            $this->redis->watch($tagsKey);
            $tags = $this->redis->sMembers($tagsKey);
            $this->redis->multi();
            $this->redis->del($this->getPrefixedIdentifier('entry:' . $entryIdentifier));
            foreach ($tags as $tag) {
                $this->redis->sRem($this->getPrefixedIdentifier('tag:' . $tag), $entryIdentifier);
            }
            $this->redis->del($this->getPrefixedIdentifier('tags:' . $entryIdentifier));
            $result = $this->redis->exec();
        } while ($result === false);

        // Reset iterator because it will be out of sync after a removal
        $this->entryIterator = null;

        return true;
    }

    /**
     * Removes all cache entries of this cache
     *
     * The flush method will use the EVAL command to flush all entries and tags for this cache
     * in an atomic way.
     *
     * @throws \RuntimeException
     * @api
     */
    public function flush(): void
    {
        // language=lua
        $script = "
        local keys = redis.call('KEYS', ARGV[1] .. '*')
        for k1,key in ipairs(keys) do
            redis.call('DEL', key)
        end
        ";
        $this->redis->eval($script, [$this->getPrefixedIdentifier('')], 0);

        $this->frozen = null;
        $this->entryIterator = null;
    }

    /**
     * This backend does not need an externally triggered garbage collection
     *
     * @api
     */
    public function collectGarbage(): void
    {
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

        // language=lua
        $script = "
        local entries = redis.call('SMEMBERS', KEYS[1])
        for k1,entryIdentifier in ipairs(entries) do
            redis.call('DEL', ARGV[1]..'entry:'..entryIdentifier)

            local tags = redis.call('SMEMBERS', ARGV[1]..'tags:'..entryIdentifier)
            for k2,tagName in ipairs(tags) do
                redis.call('SREM', ARGV[1]..'tag:'..tagName, entryIdentifier)
            end

            redis.call('DEL', ARGV[1]..'tags:'..entryIdentifier)
        end
        redis.call('DEL', KEYS[1])
        return #entries
        ";
        return $this->redis->eval($script, [$this->getPrefixedIdentifier('tag:' . $tag), $this->getPrefixedIdentifier('')], 1);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tags.
     *
     * @param array<string> $tags The tag the entries must have
     * @throws \RuntimeException
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTags(array $tags): int
    {
        if ($this->isFrozen()) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1647642328);
        }

        // language=lua
        $script = "
        local total_entries = 0
        local num_arg = #ARGV
        for i = 1, num_arg do
            local entries = redis.call('SMEMBERS', KEYS[i])
            for k1,entryIdentifier in ipairs(entries) do
                redis.call('UNLINK', ARGV[i]..'entry:'..entryIdentifier)

                local tags = redis.call('SMEMBERS', ARGV[i]..'tags:'..entryIdentifier)
                for k2,tagName in ipairs(tags) do
                    redis.call('SREM', ARGV[i]..'tag:'..tagName, entryIdentifier)
                end

                redis.call('UNLINK', ARGV[i]..'tags:'..entryIdentifier)
            end
            redis.call('UNLINK', KEYS[i])
            total_entries = total_entries + #entries
        end
        return total_entries
        ";

        $flushedEntriesTotal = 0;

        // Flush tags in batches
        for ($i = 0, $iMax = count($tags); $i < $iMax; $i += $this->batchSize) {
            $tagList = array_slice($tags, $i, $this->batchSize);
            $keys = array_map(function ($tag) {
                return $this->getPrefixedIdentifier('tag:' . $tag);
            }, $tagList);
            $values = array_fill(0, count($keys), $this->getPrefixedIdentifier(''));

            $flushedEntries = $this->redis->eval($script, array_merge($keys, $values), count($keys));
            $flushedEntriesTotal = is_int($flushedEntries) ? $flushedEntries : 0;
        }

        return $flushedEntriesTotal;
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
        return $this->redis->sMembers($this->getPrefixedIdentifier('tag:' . $tag));
    }

    /**
     * {@inheritdoc}
     */
    public function current(): string|bool
    {
        return $this->get($this->getEntryIterator()->current());
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->getEntryIterator()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): string|bool
    {
        $entryIdentifier = $this->getEntryIterator()->current();

        if (!$entryIdentifier || !$this->has($entryIdentifier)) {
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
    public function rewind(): void
    {
        $this->getEntryIterator()->rewind();
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
     */
    public function freeze(): void
    {
        if ($this->isFrozen()) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }
        do {
            $iterator = $this->getEntryIterator();
            $this->redis->multi();
            foreach ($iterator as $entryIdentifier) {
                $this->redis->persist($this->getPrefixedIdentifier('entry:' . $entryIdentifier));
            }
            /** @var array|bool $result */
            $result = $this->redis->exec();
            $this->redis->set($this->getPrefixedIdentifier('frozen'), 1);
        } while ($result === false);
        $this->frozen = true;
    }

    /**
     * Tells if this backend is frozen.
     */
    public function isFrozen(): bool
    {
        if (null === $this->frozen) {
            $this->frozen = (bool)$this->redis->exists($this->getPrefixedIdentifier('frozen'));
        }

        return $this->frozen;
    }

    /**
     * Sets the hostname or the socket of the Redis server
     * @api
     */
    public function setHostname(string $hostname): void
    {
        $this->hostname = $hostname;
    }

    /**
     * Sets the port of the Redis server.
     *
     * Unused if you want to connect to a socket (i.e. hostname contains a /)
     * @api
     */
    public function setPort(int|string $port): void
    {
        $this->port = (int)$port;
    }

    /**
     * Sets the database that will be used for this backend
     * @api
     */
    public function setDatabase(int|string $database): void
    {
        $this->database = (int)$database;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setCompressionLevel(int|string $compressionLevel): void
    {
        $this->compressionLevel = (int)$compressionLevel;
    }

    /**
     * Sets the Maximum number of items for batch operations
     *
     * @api
     */
    public function setBatchSize(int|string $batchSize): void
    {
        $this->batchSize = (int)$batchSize;
    }

    public function setRedis(\Redis $redis = null): void
    {
        if ($redis !== null) {
            $this->redis = $redis;
        }
    }

    private function uncompress(bool|string $value): bool|string
    {
        if (empty($value)) {
            return $value;
        }
        return $this->useCompression() ? gzdecode((string) $value) : $value;
    }

    private function compress(string $value): string
    {
        return $this->useCompression() ? gzencode($value, $this->compressionLevel) : $value;
    }

    private function useCompression(): bool
    {
        return $this->compressionLevel > 0;
    }

    /**
     * @throws CacheException
     */
    private function getRedisClient(): \Redis
    {
        $redis = new \Redis();

        try {
            $connected = false;
            // keep the assignment above! the connect calls below leaves the variable undefined, if an error occurs.
            if (str_contains($this->hostname, '/')) {
                $connected = $redis->connect($this->hostname);
            } else {
                $connected = $redis->connect($this->hostname, $this->port);
            }
        } finally {
            /** @psalm-suppress PossiblyUndefinedVariable */
            if ($connected === false) {
                throw new CacheException('Could not connect to Redis.', 1391972021);
            }
        }

        if ($this->password !== '' && !$redis->auth($this->password)) {
            throw new CacheException('Redis authentication failed.', 1502366200);
        }
        $redis->select($this->database);
        return $redis;
    }

    /**
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

    /**
     * Create iterator over all entry keys in the cache, prefixed by its identifier
     */
    private function getEntryIterator(): \Iterator
    {
        if (!$this->entryIterator) {
            $prefix = $this->getPrefixedIdentifier('entry:');
            $prefixLength = strlen($prefix);
            $keys = $this->redis->keys($prefix . '*');
            if (is_array($keys)) {
                $entryIdentifiers = array_map(static fn (string $key) => substr($key, $prefixLength), $keys);
            } else {
                $entryIdentifiers = [];
            }
            $this->entryIterator = new \ArrayIterator($entryIdentifiers);
        }
        return $this->entryIterator;
    }
}

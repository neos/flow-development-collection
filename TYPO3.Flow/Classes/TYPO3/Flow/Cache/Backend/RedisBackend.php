<?php
namespace TYPO3\Flow\Cache\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cache\Exception as CacheException;

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
class RedisBackend extends AbstractBackend implements TaggableBackendInterface, IterableBackendInterface, FreezableBackendInterface {

	/**
	 * @var \Redis
	 */
	protected $redis;

	/**
	 * @var integer Cursor used for iterating over cache entries
	 */
	protected $entryCursor = 0;

	/**
	 * @var boolean
	 */
	protected $frozen = NULL;

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
	 * @param \TYPO3\Flow\Core\ApplicationContext $context
	 * @param array $options
	 * @param \Redis $redis
	 * @throws CacheException
	 */
	public function __construct(\TYPO3\Flow\Core\ApplicationContext $context, array $options = array(), \Redis $redis = NULL) {
		parent::__construct($context, $options);
		if (NULL === $redis) {
			$redis = $this->getRedisClient();
		}
		$this->redis = $redis;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @throws \RuntimeException
	 * @return void
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if ($this->isFrozen()) {
			throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
		}

		if ($lifetime === NULL) {
			$lifetime = $this->defaultLifetime;
		}

		$setOptions = array();
		if ($lifetime > 0) {
			$setOptions['ex'] = $lifetime;
		}

		$this->redis->multi();
		$this->redis->set($this->buildKey('entry:' . $entryIdentifier), $data, $setOptions);
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
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @api
	 */
	public function get($entryIdentifier) {
		return $this->redis->get($this->buildKey('entry:' . $entryIdentifier));
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function has($entryIdentifier) {
		return $this->redis->exists($this->buildKey('entry:' . $entryIdentifier));
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @throws \RuntimeException
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @api
	 */
	public function remove($entryIdentifier) {
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
			$result = $this->redis->exec();
		} while ($result === FALSE);
		return TRUE;
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
	public function flush() {
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
		$this->redis->eval($script, array($this->buildKey('entries'), $this->buildKey('frozen'), $this->buildKey('')), 2);

		$this->frozen = NULL;
	}

	/**
	 * This backend does not need an externally triggered garbage collection
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {

	}

	/**
	 * @param $identifier
	 * @return string
	 */
	private function buildKey($identifier) {
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
	public function flushByTag($tag) {
		if ($this->isFrozen()) {
			throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
		}

		$script = "
		local entries = redis.call('SMEMBERS',KEYS[1])
		for k1,entryIdentifier in ipairs(entries) do
			redis.call('DEL', ARGV[1]..'entry:'..entryIdentifier)
			local tags = redis.call('SMEMBERS', ARGV[1]..'tags:'..entryIdentifier)
			for k2,tagName in ipairs(tags) do
				redis.call('SREM', ARGV[1]..'tag:'..tagName, entryIdentifier)
			end
			redis.call('DEL', ARGV[1]..'tags:'..entryIdentifier)
		end
		return #entries
		";
		$count = $this->redis->eval($script, array($this->buildKey('tag:' . $tag), $this->buildKey('')), 1);

		return $count;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		return $this->redis->sMembers($this->buildKey('tag:' . $tag));
	}

	/**
	 * {@inheritdoc}
	 */
	public function current() {
		return $this->get($this->key());
	}

	/**
	 * {@inheritdoc}
	 */
	public function next() {
		$this->entryCursor++;
	}

	/**
	 * {@inheritdoc}
	 */
	public function key() {
		return $this->redis->lIndex($this->buildKey('entries'), $this->entryCursor);
	}

	/**
	 * {@inheritdoc}
	 */
	public function valid() {
		return $this->key() !== FALSE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function rewind() {
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
	public function freeze() {
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
			$this->redis->set($this->buildKey('frozen'), 1);
			$result = $this->redis->exec();
		} while ($result === FALSE);
		$this->frozen = TRUE;
	}

	/**
	 * Tells if this backend is frozen.
	 *
	 * @return boolean
	 */
	public function isFrozen() {
		if (NULL === $this->frozen) {
			$this->frozen = $this->redis->exists($this->buildKey('frozen'));
		}
		return $this->frozen;
	}

	/**
	 * Sets the default lifetime for this cache backend
	 *
	 * @param integer $lifetime
	 * @param integer $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. 0 means unlimited lifetime.
	 * @return void
	 * @api
	 */
	public function setDefaultLifetime($lifetime) {
		$this->defaultLifetime = $lifetime;
	}

	/**
	 * Sets the hostname or the socket of the Redis server
	 *
	 * @param string $hostname
	 * @param string $hostname Hostname of the Redis server
	 * @api
	 */
	public function setHostname($hostname) {
		$this->hostname = $hostname;
	}

	/**
	 * Sets the port of the Redis server.
	 *
	 * Leave this empty if you want to connect to a socket
	 *
	 * @param string $port
	 * @param string $port Port of the Redis server
	 * @api
	 */
	public function setPort($port) {
		$this->port = $port;
	}

	/**
	 * Sets the database that will be used for this backend
	 *
	 * @param integer $database
	 * @param integer $database Database that will be used
	 * @api
	 */
	public function setDatabase($database) {
		$this->database = $database;
	}

	/**
	 * @return \Redis
	 * @throws CacheException
	 */
	private function getRedisClient() {
		if (strpos($this->hostname, '/') !== FALSE) {
			$this->port = NULL;
		}
		$redis = new \Redis();
		if (!$redis->connect($this->hostname, $this->port)) {
			throw new CacheException('Could not connect to Redis.', 1391972021);
		}
		$redis->select($this->database);
		return $redis;
	}

}

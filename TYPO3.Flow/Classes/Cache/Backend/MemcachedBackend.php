<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A caching backend which stores cache entries by using Memcached.
 *
 * This backend uses the following types of Memcache keys:
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 * - tagIndex
 *   Value is a List of all tags (array)
 *
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "FLOW3"
 * - MD5 of script path and filename and SAPI name
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * Note: When using the Memcached backend to store values of more than ~1 MB, the
 * data will be split into chunks to make them fit into the memcached limits.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class MemcachedBackend extends \F3\FLOW3\Cache\Backend\AbstractBackend {

	/**
	 * Max bucket size, (1024*1024)-42 bytes
	 * @var int
	 */
	const MAX_BUCKET_SIZE = 1048534;

	/**
	 * Instance of the PHP Memcache class
	 *
	 * @var Memcache
	 */
	protected $memcache;

	/**
	 * Array of Memcache server configurations
	 *
	 * @var array
	 */
	protected $servers = array();

	/**
	 * Indicates whether the memcache uses compression or not (requires zlib),
	 * either 0 or MEMCACHE_COMPRESSED
	 *
	 * @var int
	 */
	protected $flags;

	/**
	 * A prefix to seperate stored data from other data possible stored in the memcache
	 *
	 * @var string
	 */
	protected $identifierPrefix;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Constructs this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context, $options = array()) {
		if (!extension_loaded('memcache')) throw new \F3\FLOW3\Cache\Exception('The PHP extension "memcached" must be installed and loaded in order to use the Memcached backend.', 1213987706);
		parent::__construct($context, $options);
	}

	/**
	 * Injects the environment utility
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Setter for servers to be used. Expects an array,  the values are expected
	 * to be formatted like "<host>[:<port>]" or "unix://<path>"
	 *
	 * @param array $servers An array of servers to add.
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @api
	 */
	protected function setServers(array $servers) {
		$this->servers = $servers;
	}

	/**
	 * Setter for compression flags bit
	 *
	 * @param boolean $useCompression
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @api
	 */
	protected function setCompression($useCompression) {
		if ($useCompression === TRUE) {
			$this->flags ^= MEMCACHE_COMPRESSED;
		} else {
			$this->flags &= ~MEMCACHE_COMPRESSED;
		}
	}

	/**
	 * Initializes the identifier prefix
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Dmitry Dulepov <dmitry@typo3.org>
	 */
	public function initializeObject() {
		if (!count($this->servers)) throw new \F3\FLOW3\Cache\Exception('No servers were given to Memcache', 1213115903);

		$this->memcache = new \Memcache();
		$defaultPort = ini_get('memcache.default_port');

		foreach ($this->servers as $server) {
			if (substr($server, 0, 7) === 'unix://') {
				$host = $server;
				$port = 0;
			} else {
				if (substr($server, 0, 6) === 'tcp://') {
					$server = substr($server, 6);
				}
				if (strstr($server, ':') !== FALSE) {
					list($host, $port) = explode(':', $server, 2);
				} else {
					$host = $server;
					$port = $defaultPort;
				}
			}
			$this->memcache->addServer($host, $port);
		}
	}

	/**
	 * Initializes the identifier prefix when setting the cache.
	 *
	 * @param \F3\FLOW3\Cache\Frontend\FrontendInterface $cache
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setCache(\F3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);
		$this->identifierPrefix = 'FLOW3_' . md5($cache->getIdentifier() . $this->environment->getScriptPathAndFilename() . $this->environment->getSAPIName()) . '_';
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \F3\FLOW3\Cache\Exception if no cache frontend has been set.
	 * @throws \InvalidArgumentException if the identifier is not valid or the final memcached key is longer than 250 characters
	 * @throws \F3\FLOW3\Cache\Exception\InvalidDataException if $data is not a string
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (strlen($this->identifierPrefix . $entryIdentifier) > 250) throw new \InvalidArgumentException('Could not set value. Key more than 250 characters (' . $this->identifierPrefix . $entryIdentifier . ').', 1232969508);
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('No cache frontend has been set yet via setCache().', 1207149215);
		if (!is_string($data)) throw new \F3\FLOW3\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1207149231);
		$this->systemLogger->log(sprintf('Cache %s: setting entry "%s".', $this->cacheIdentifier, $entryIdentifier), LOG_DEBUG);

		$tags[] = '%MEMCACHEBE%' . $this->cacheIdentifier;
		$expiration = $lifetime !== NULL ? $lifetime : $this->defaultLifetime;
			// Memcached consideres values over 2592000 sec (30 days) as UNIX timestamp
			// thus $expiration should be converted from lifetime to UNIX timestamp
		if ($expiration > 2592000) {
			$expiration += time();
		}

		try {
			if(strlen($data) > self::MAX_BUCKET_SIZE) {
				$data = str_split($data, self::MAX_BUCKET_SIZE - 1024);
				$success = TRUE;
				$chunkNumber = 1;
				foreach ($data as $chunk) {
					$success = $success && $this->memcache->set($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber, $chunk, $this->flags, $expiration);
					$chunkNumber++;
				}
				$success = $success && $this->memcache->set($this->identifierPrefix . $entryIdentifier, 'FLOW3*chunked:' . $chunkNumber, $this->flags, $expiration);
			} else {
				$success = $this->memcache->set($this->identifierPrefix . $entryIdentifier, $data, $this->flags, $expiration);
			}
			if ($success === TRUE) {
				$this->removeIdentifierFromAllTags($entryIdentifier);
				$this->addIdentifierToTags($entryIdentifier, $tags);
			} else {
				throw new \F3\FLOW3\Cache\Exception('Could not set value on memcache server.', 1275830266);
			}
		} catch (\Exception $exception) {
			throw new \F3\FLOW3\Cache\Exception('Could not set value. ' . $exception->getMessage(), 1207208100);
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function get($entryIdentifier) {
		$value = $this->memcache->get($this->identifierPrefix . $entryIdentifier);
		if (substr($value, 0, 14) === 'FLOW3*chunked:') {
			list( , $chunkCount) = explode(':', $value);
			$value = '';
			for ($chunkNumber = 1 ; $chunkNumber < $chunkCount; $chunkNumber++) {
				$value .= $this->memcache->get($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber);
			}
		}
		return $value;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function has($entryIdentifier) {
		return $this->memcache->get($this->identifierPrefix . $entryIdentifier) !== FALSE;
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function remove($entryIdentifier) {
		$this->systemLogger->log(sprintf('Cache %s: removing entry "%s".', $this->cacheIdentifier, $entryIdentifier), LOG_DEBUG);
		$this->removeIdentifierFromAllTags($entryIdentifier);
		return $this->memcache->delete($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		$identifiers = $this->memcache->get($this->identifierPrefix . 'tag_' . $tag);
		if ($identifiers !== FALSE) {
			return (array) $identifiers;
		} else {
			return array();
		}
	}

	/**
	 * Finds all tags for the given identifier. This function uses reverse tag
	 * index to search for tags.
	 *
	 * @param string $identifier Identifier to find tags by
	 * @return array Array with tags
	 * @author Dmitry Dulepov <dmitry@typo3.org>
	 */
	protected function findTagsByIdentifier($identifier) {
		$tags = $this->memcache->get($this->identifierPrefix . 'ident_' . $identifier);
		return ($tags === FALSE ? array() : (array)$tags);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function flush() {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$this->flushByTag('%MEMCACHEBE%' . $this->cacheIdentifier);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		$this->systemLogger->log(sprintf('Cache %s: removing %s entries matching tag "%s"', $this->cacheIdentifier, count($identifiers), $tag), LOG_INFO);
		foreach ($identifiers as $identifier) {
			$this->remove($identifier);
		}
	}

	/**
	 * Associates the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array $tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Dmitry Dulepov
	 */
	protected function addIdentifierToTags($entryIdentifier, array $tags) {
		foreach ($tags as $tag) {
				// Update tag-to-identifier index
			$identifiers = $this->findIdentifiersByTag($tag);
			if (array_search($entryIdentifier, $identifiers) === FALSE) {
				$identifiers[] = $entryIdentifier;
				$this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
			}

				// Update identifier-to-tag index
			$existingTags = $this->findTagsByIdentifier($entryIdentifier);
			if (array_search($tag, $existingTags) === FALSE) {
				$this->memcache->set($this->identifierPrefix . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
			}

		}
	}

	/**
	 * Removes association of the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array $tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Dmitry Dulepov
	 */
	protected function removeIdentifierFromAllTags($entryIdentifier) {
			// Get tags for this identifier
		$tags = $this->findTagsByIdentifier($entryIdentifier);
			// Deassociate tags with this identifier
		foreach ($tags as $tag) {
			$identifiers = $this->findIdentifiersByTag($tag);
				// Formally array_search() below should never return false due to
				// the behavior of findTagsByIdentifier(). But if reverse index is
				// corrupted, we still can get 'false' from array_search(). This is
				// not a problem because we are removing this identifier from
				// anywhere.
			if (($key = array_search($entryIdentifier, $identifiers)) !== FALSE) {
				unset($identifiers[$key]);
				if (count($identifiers)) {
					$this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
				} else {
					$this->memcache->delete($this->identifierPrefix . 'tag_' . $tag);
				}
			}
		}
			// Clear reverse tag index for this identifier
		$this->memcache->delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
	}

	/**
	 * Does nothing, as memcached does GC itself
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
		$this->systemLogger->log(sprintf('Cache %s: garbage collection is done by memcached', $this->cacheIdentifier), LOG_INFO);
	}

}

?>
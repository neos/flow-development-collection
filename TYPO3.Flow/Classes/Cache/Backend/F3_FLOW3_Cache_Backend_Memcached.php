<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 */

/**
 *  A caching backend which stores cache entries by using Memcached
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Cache_Backend_Memcached extends F3_FLOW3_Cache_AbstractBackend {

	/**
	 * @var Memcache
	 */
	protected $memcache;

	/**
	 * @var array
	 */
	protected $servers = array();

	/**
	 * @var boolean whether the memcache uses compression or not (requires zlib)
	 */
	protected $useCompressed;

	/**
	 * @var string A prefix to seperate stored data from other data possible stored in the memcache
	 */
	protected $identifierPrefix;

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $environment;

	/**
	 * Constructs this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context, $options = array()) {
		if (!extension_loaded('memcache')) throw new F3_FLOW3_Cache_Exception('The PHP extension "memcached" must be installed and loaded in order to use the Memcached backend.', 1213987706);
		parent::__construct($context, $options);
	}

	/**
	 * Injects the environment utility
	 *
	 * @param F3_FLOW3_Utility_Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(F3_FLOW3_Utility_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * setter for servers property
	 * should be an array of entries like host:port
	 *
	 * @param array $servers
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	protected function setServers(array $servers) {
		$this->servers = $servers;
	}

	/**
	 * Initializes the identifier prefix
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeComponent() {
		$this->memcache = new Memcache();
		$this->identifierPrefix = 'FLOW3_' . md5($this->environment->getScriptPathAndFilename() . $this->environment->getSAPIName()) . '_';

		if (!count($this->servers)) throw new F3_FLOW3_Cache_Exception('No servers were given to Memcache', 1213115903);
		foreach ($this->servers as $serverConf) {
			$conf = explode(':',$serverConf, 2);
			$this->memcache->addServer($conf[0], $conf[1]);
		}
	}

	/**
	 * Setter for useCompressed
	 *
	 * @param boolean $enableCompression
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	protected function setCompression($enableCompression) {
		$this->useCompressed = $enableCompression;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws F3_FLOW3_Cache_Exception if no cache frontend has been set.
	 * @throws InvalidArgumentException if the identifier is not valid
	 * @throws F3_FLOW3_Cache_Exception_InvalidData if $data is not a string
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 **/
	public function save($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!self::isValidEntryIdentifier($entryIdentifier)) throw new InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1207149191);
		if (!$this->cache instanceof F3_FLOW3_Cache_AbstractCache) throw new F3_FLOW3_Cache_Exception('No cache frontend has been set yet via setCache().', 1207149215);
		if (!is_string($data)) throw new F3_FLOW3_Cache_Exception_InvalidData('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1207149231);
		foreach($tags as $tag) {
			if (!self::isValidTag($tag))  throw new InvalidArgumentException('"' . $tag . '" is not a valid tag.', 1213120275);
		}

		$expiration = $lifetime ? $lifetime : $this->defaultLifetime;
		try {
			$this->remove($entryIdentifier);
			$success = $this->memcache->set($this->identifierPrefix . $entryIdentifier, $data, $this->useCompressed, $expiration);
			if (!$success) throw new F3_FLOW3_Cache_Exception('Memcache was unable to connect to any server.', 1207165277);
			$this->addTagsToTagIndex($tags);
			$this->addIdentifierToTags($entryIdentifier, $tags);
		} catch(F3_FLOW3_Error_Exception $exception) {
			throw new F3_FLOW3_Cache_Exception('Memcache was unable to connect to any server. ' . $exception->getMessage(), 1207208100);
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier: An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function load($entryIdentifier) {
		return $this->memcache->get($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier: An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function has($entryIdentifier) {
		return (boolean) $this->memcache->get($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier: Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove($entryIdentifier) {
		$this->removeIdentifierFromAllTags($entryIdentifier);
		return $this->memcache->delete($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string $tag The tag to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo implement wildcard support
	 */
	public function findEntriesByTag($tag) {
		if (!self::isValidTag($tag))  throw new InvalidArgumentException('"' . $tag . '" is not a valid tag.', 1213120307);

		return $this->findIdentifiersTaggedWith($tag);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * Beware that this flushes the complete memcached, not only the cache
	 * entries we stored there. We do this because:
	 *  it is expensive to keep track of all identifiers we put there
	 *  memcache is a cache, you should never rely on things being there
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flush() {
		$this->memcache->flush();
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersTaggedWith($tag);
		foreach($identifiers as $identifier) {
			$this->remove($identifier);
		}
	}

	/**
	 * Returns an array with all known tags
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getTagIndex() {
		return (array)$this->memcache->get($this->identifierPrefix . '_tagIndex');
	}

	/**
	 * Saves the tags known to the backend
	 *
	 * @param array $tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setTagIndex(array $tags) {
		$this->memcache->set($this->identifierPrefix . '_tagIndex', array_unique($tags), 0, 0);
	}

	/**
	 * Adds the given tags to the tag index
	 *
	 * @param array $tags
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function addTagsToTagIndex(array $tags) {
		if(count($tags)) {
			$this->setTagIndex(array_merge($tags, $this->getTagIndex()));
		}
	}

	/**
	 * Removes the given tags from the tag index
	 *
	 * @param array $tags
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeTagsFromTagIndex(array $tags) {
		if(count($tags)) {
			$this->setTagIndex(array_diff($this->getTagIndex(), $tags));
		}
	}

	/**
	 * Associates the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array $tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function addIdentifierToTags($entryIdentifier, array $tags) {
		foreach($tags as $tag) {
			$identifiers = $this->findIdentifiersTaggedWith($tag);
			$identifiers[] = $entryIdentifier;
			$this->memcache->set($this->identifierPrefix . '_tag_' . $tag, array_unique($identifiers));
		}
	}

	/**
	 * Removes association of the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array $tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeIdentifierFromAllTags($entryIdentifier) {
		$tags = $this->getTagIndex();
		foreach($tags as $tag) {
			$identifiers = $this->findIdentifiersTaggedWith($tag);
			if(array_search($entryIdentifier, $identifiers) !== FALSE) {
				unset($identifiers[array_search($entryIdentifier, $identifiers)]);
			}
			if(count($identifiers)) {
				$this->memcache->set($this->identifierPrefix . '_tag_' . $tag, array_unique($identifiers));
			} else {
				$this->removeTagsFromTagIndex(array($tag));
				$this->memcache->delete($this->identifierPrefix . '_tag_' . $tag);
			}
		}
	}

	/**
	 * Returns all identifiers associated with $tag
	 *
	 * @param string $tag
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersTaggedWith($tag) {
		$identifiers = $this->memcache->get($this->identifierPrefix . '_tag_' . $tag);
		if($identifiers !== FALSE) {
			return (array) $identifiers;
		} else {
			return array();
		}
	}
}

?>
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
	protected $servers;

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
	 * Injects the environment utility
	 *
	 * @param F3_FLOW3_Utility_Environment $environment
	 * @return void
	 * @required
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(F3_FLOW3_Utility_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Initializes the identifier prefix
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeComponent() {
		$this->identifierPrefix = 'FLOW3_' . md5($this->environment->getScriptPathAndFilename() . $this->environment->getSAPIName()) . '_';
	}

	/**
	 * setter for servers property
	 * should be an array of entries like host:port
	 *
	 * @param array $serverConf
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function setServers(array $serverConf) {
		$this->servers = $serverConf;
	}

	/**
	 * getter for servers property
	 *
	 * @return array
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function getServers() {
		return $this->servers;
	}

	/**
	 * Setter for useCompressed
	 *
	 * @param boolean $enableCompression
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function setCompression($enableCompression) {
		$this->useCompressed = $enableCompression;
	}

	/**
	 * Getter for useCompressed
	 *
	 * @return boolean If compression can / should be used or not
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	public function getCompression() {
		return $this->useCompressed;
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
	public function save($entryIdentifier, $data, $tags = array(), $lifetime = NULL) {
		if (!self::isValidEntryIdentifier($entryIdentifier)) throw new InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1207149191);
		if (!$this->cache instanceof F3_FLOW3_Cache_AbstractCache) throw new F3_FLOW3_Cache_Exception('No cache frontend has been set yet via setCache().', 1207149215);
		if (!is_string($data)) throw new F3_FLOW3_Cache_Exception_InvalidData('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1207149231);
		if (count($tags)) throw new F3_FLOW3_Cache_Exception('Tagging is not yet supported by the memcache backend.', 1213111770);

		$expiration = $lifetime ? $lifetime : $this->defaultLifetime;
		try {
			$success = $this->getMemcache()->set($this->identifierPrefix . $entryIdentifier, $data, $this->useCompressed, $expiration);
			if (!$success) throw new F3_FLOW3_Cache_Exception('Memcache was unable to connect to any server',1207165277);
		} catch(F3_FLOW3_Error_Exception $exception) {
			throw new F3_FLOW3_Cache_Exception($exception->getMessage(), 1207208100);
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
		return $this->getMemcache()->get($this->identifierPrefix . $entryIdentifier);
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
		return (boolean) $this->getMemcache()->get($this->identifierPrefix . $entryIdentifier);
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
		return $this->getMemcache()->delete($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string $tag The tag to search for, the "*" wildcard is supported
	 * @return array An array of F3_FLOW3_Cache_Entry with all matching entries. An empty array if no entries matched
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findEntriesByTag($tag) {
		throw new F3_FLOW3_Cache_Exception('Tagging is not yet supported by the memcache backend.', 1213111771);
	}

	/**
	 * Creates and/or returns the memcache instance
	 *
	 * @return Memcache
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	protected function getMemcache() {
		if (!$this->memcache instanceof Memcache) {
			$this->memcache = new Memcache();
			$this->setupMemcache($this->memcache);
		}
		return $this->memcache;
	}

	/**
	 * Setting up the Memcache, just adding servers for now.
	 *
	 * @param Memcache $memcache
	 * @return void
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	protected function setupMemcache(Memcache $memcache) {
		if (!is_array($this->getServers()) or sizeof($this->getServers())==0) throw new F3_FLOW3_Cache_Exception('No servers was configured for Memcache',1207161347);
		foreach ($this->servers as $serverConf) {
			$conf = explode(':',$serverConf,2);
			$memcache->addServer($conf[0],$conf[1]);
		}
	}
}

?>
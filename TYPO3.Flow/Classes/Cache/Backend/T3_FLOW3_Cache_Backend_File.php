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
 * @version $Id: $
 */

/**
 * A caching backend which stores cache entries in files
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:T3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class T3_FLOW3_Cache_Backend_File extends T3_FLOW3_Cache_AbstractBackend {

	/**
	 * @var T3_FLOW3_Cache_AbstractCache Reference to the cache which uses this backend
	 */
	protected $cache;

	/**
	 * @var string The current application context
	 */
	protected $context;

	/**
	 * @var string Directory where the files are stored
	 */
	protected $cacheDirectory = '';

	/**
	 * @var integer Default lifetime of a cache entry in seconds
	 */
	protected $defaultLifetime = 3600;

	/**
	 * Constructs this backend
	 *
	 * @param string $context: FLOW3's application context
	 * @param T3_FLOW3_Utility_Environment $environment: The environment - used for setting the default cache directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context) {
		$this->context = $context;
	}

	/**
	 * Injects the environment utility
	 *
	 * @param T3_FLOW3_Utility_Environment $environment
	 * @return void
	 * @required
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(T3_FLOW3_Utility_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Initializes the default cache directory
	 *
	 * @return void
	 */
	public function initializeComponent() {
		$cacheDirectory = $this->environment->getPathToTemporaryDirectory() . '/FLOW3/' . md5($this->environment->getScriptPathAndFilename()) . '/';
		try {
			$this->setCacheDirectory($cacheDirectory);
		} catch(T3_FLOW3_Cache_Exception $exception) {
		}
	}

	/**
	 * Sets a reference to the cache which uses this backend
	 *
	 * @param T3_FLOW3_Cache_AbstractCache $cache The frontend for this backend
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCache(T3_FLOW3_Cache_AbstractCache $cache) {
		$this->cache = $cache;
	}

	/**
	 * Sets the directory where the cache files are stored.
	 *
	 * @param string $cacheDirectory: The directory
	 * @return void
	 * @throws T3_FLOW3_Cache_Exception if the directory does not exist, is not writable or could not be created.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectory($cacheDirectory) {
		if (!is_writable($cacheDirectory)) {
			T3_FLOW3_Utility_Files::createDirectoryRecursively($cacheDirectory);
		}
		if (!is_dir($cacheDirectory)) throw new T3_FLOW3_Cache_Exception('The directory "' . $cacheDirectory . '" does not exist.', 1203965199);
		if (!is_writable($cacheDirectory)) throw new T3_FLOW3_Cache_Exception('The directory "' . $cacheDirectory . '" is not writable.', 1203965200);
		$this->cacheDirectory = $cacheDirectory;
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectory() {
		return $this->cacheDirectory;
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string $data: The data to be stored
	 * @param string $entryIdentifier: An identifier for this specific cache entry
	 * @param array $tags: Tags to associate with this cache entry
	 * @param integer $lifetime: Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws T3_FLOW3_Cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function save($data, $entryIdentifier, $tags = array(), $lifetime = NULL) {
		if (!is_object($this->cache)) throw new T3_FLOW3_Cache_Exception('Yet no cache frontend has been set via setCache().', 1204111375);

		if ($lifetime === NULL) $lifetime = $this->defaultLifetime;
		$expiryTime = new DateTime('now +' . $lifetime . ' seconds', new DateTimeZone('UTC'));
		$dataHash = sha1($data);
		$path = $this->cacheDirectory . '/' . $this->context . '/Cache/' . $this->cache->getIdentifier() . '/' . $dataHash{0} . '/' . $dataHash {1} . '/';
		$filename = $this->renderCacheFilename($entryIdentifier, $expiryTime, $dataHash);

		if (!is_writable($path)) {
			T3_FLOW3_Utility_Files::createDirectoryRecursively($path);
			if (!is_writable($path)) throw new T3_FLOW3_Cache_Exception('The cache directory "' . $path . '" could not be created.', 1204026250);
		}

		$temporaryFilename = $filename . '.' . uniqid() . '.temp';
		$result = @file_put_contents($path . $temporaryFilename, $data);
		if ($result === FALSE) throw new T3_FLOW3_Cache_Exception('The temporary cache file "' . $temporaryFilename . '" could not be written.', 1204026251);
		for ($i=0; $i<5; $i++) {
			$result = rename($path . $temporaryFilename, $path . $filename);
			if ($result === TRUE) break;
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier: An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function load($entryIdentifier) {
		if (!is_object($this->cache)) throw new T3_FLOW3_Cache_Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$path = $this->cacheDirectory . $this->context . '/Cache/' . $this->cache->getIdentifier() . '/';
		$pattern = $path . '*/*/????-??-?????;??;???_' . $entryIdentifier . '*.cachedata';
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) == 0) return FALSE;
		return file_get_contents(array_pop($filesFound));
	}

	/**
	 * Renders a file name for the specified cache entry
	 *
	 * @param string $identifier: Identifier for the cache entry
	 * @param DateTime $expiry: Date and time specifying the expiration of the entry. Must be a UTC time.
	 * @param string $dataHash: 40 bytes hexadecimal SHA1 hash of the data to be stored
	 * @return string Filename of the cache data file
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function renderCacheFilename($identifier, DateTime $expiryTime, $dataHash) {
		$filename = $expiryTime->format('Y-m-d\TH\;i\;s\Z') . '_' . $identifier . '_' . $dataHash . '.cachedata';
		return $filename;
	}
}
?>
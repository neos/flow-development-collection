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
 * A caching backend which stores cache entries in files
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Cache_Backend_File extends F3_FLOW3_Cache_AbstractBackend {

	/**
	 * @var string Directory where the files are stored
	 */
	protected $cacheDirectory = '';

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
	 * Initializes the default cache directory
	 *
	 * @return void
	 */
	public function initializeComponent() {
		$pathHash = md5($this->environment->getScriptPathAndFilename() . $this->environment->getSAPIName());
		$cacheDirectory = $this->environment->getPathToTemporaryDirectory() . '/FLOW3/' . $pathHash . '/';
		try {
			$this->setCacheDirectory($cacheDirectory);
		} catch(F3_FLOW3_Cache_Exception $exception) {
		}
	}

	/**
	 * Sets the directory where the cache files are stored.
	 *
	 * @param string $cacheDirectory: The directory
	 * @return void
	 * @throws F3_FLOW3_Cache_Exception if the directory does not exist, is not writable or could not be created.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectory($cacheDirectory) {
		if (!is_writable($cacheDirectory)) {
			F3_FLOW3_Utility_Files::createDirectoryRecursively($cacheDirectory);
		}
		if (!is_dir($cacheDirectory)) throw new F3_FLOW3_Cache_Exception('The directory "' . $cacheDirectory . '" does not exist.', 1203965199);
		if (!is_writable($cacheDirectory)) throw new F3_FLOW3_Cache_Exception('The directory "' . $cacheDirectory . '" is not writable.', 1203965200);
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
	 * @param string $entryIdentifier: An identifier for this specific cache entry
	 * @param string $data: The data to be stored
	 * @param array $tags: Tags to associate with this cache entry
	 * @param integer $lifetime: Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws F3_FLOW3_Cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function save($entryIdentifier, $data, $tags = array(), $lifetime = NULL) {
		if (!$this->checkEntryIdentifierValidity($entryIdentifier)) throw new InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1207139693);
		if (!is_object($this->cache)) throw new F3_FLOW3_Cache_Exception('No cache frontend has been set yet via setCache().', 1204111375);
		if (!is_string($data)) throw new F3_FLOW3_Cache_Exception_InvalidData('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1204481674);

		if ($lifetime === NULL) $lifetime = $this->defaultLifetime;
		$expiryTime = new DateTime('now +' . $lifetime . ' seconds', new DateTimeZone('UTC'));
		$entryIdentifierHash = sha1($entryIdentifier);
		$path = $this->cacheDirectory . '/' . $this->context . '/Cache/' . $this->cache->getIdentifier() . '/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash {1} . '/';
		$filename = $this->renderCacheFilename($entryIdentifier, $expiryTime);

		if (!is_writable($path)) {
			try {
				F3_FLOW3_Utility_Files::createDirectoryRecursively($path);
			} catch(Exception $exception) {
			}
			if (!is_writable($path)) throw new F3_FLOW3_Cache_Exception('The cache directory "' . $path . '" could not be created.', 1204026250);
		}

		$this->remove($entryIdentifier);

		$temporaryFilename = $filename . '.' . uniqid() . '.temp';
		$result = @file_put_contents($path . $temporaryFilename, $data);
		if ($result === FALSE) throw new F3_FLOW3_Cache_Exception('The temporary cache file "' . $temporaryFilename . '" could not be written.', 1204026251);
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
		$pathsAndFilenames = $this->findCacheFiles($entryIdentifier);
		return ($pathsAndFilenames !== FALSE) ? file_get_contents(array_pop($pathsAndFilenames)) : FALSE;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param unknown_type $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function has($entryIdentifier) {
		return $this->findCacheFiles($entryIdentifier) !== FALSE;
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier: Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFiles($entryIdentifier);
		if ($pathsAndFilenames === FALSE) return FALSE;

		foreach ($pathsAndFilenames as $pathAndFilename) {
			$result = unlink ($pathAndFilename);
			if ($result === FALSE) return FALSE;
		}
		return TRUE;
	}

	/**
	 * Renders a file name for the specified cache entry
	 *
	 * @param string $identifier: Identifier for the cache entry
	 * @param DateTime $expiry: Date and time specifying the expiration of the entry. Must be a UTC time.
	 * @return string Filename of the cache data file
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function renderCacheFilename($identifier, DateTime $expiryTime) {
		$filename = $expiryTime->format('Y-m-d\TH\;i\;s\Z') . '_' . $identifier . '.cachedata';
		return $filename;
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 * Usually only one cache entry should be found - if more than one exist, this
	 * is due to some error or crash.
	 *
	 * @param string $identifier: The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_Cache_Exception if no frontend has been set
	 */
	protected function findCacheFiles($entryIdentifier) {
		if (!is_object($this->cache)) throw new F3_FLOW3_Cache_Exception('Yet no cache frontend has been set via setCache().', 1204111376);
		$path = $this->cacheDirectory . $this->context . '/Cache/' . $this->cache->getIdentifier() . '/';
		$pattern = $path . '*/*/????-??-?????;??;???_' . $entryIdentifier . '.cachedata';
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) == 0) return FALSE;
		return $filesFound;
	}
}
?>
<?php
namespace TYPO3\FLOW3\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A caching backend which stores cache entries in files
 *
 * @api
 */
class FileBackend extends \TYPO3\FLOW3\Cache\Backend\AbstractBackend implements \TYPO3\FLOW3\Cache\Backend\PhpCapableBackendInterface {

	const SEPARATOR = '^';

	const EXPIRYTIME_FORMAT = 'YmdHis';
	const EXPIRYTIME_LENGTH = 14;

	const DATASIZE_DIGITS = 10;

	/**
	 * Directory where the files are stored.
	 *
	 * @var string
	 */
	protected $cacheDirectory = '';

	/**
	 * A file extension to use for each cache entry.
	 *
	 * @var string
	 */
	protected $cacheEntryFileExtension = '';

	/**
	 * Sets a reference to the cache frontend which uses this backend and
	 * initializes the default cache directory
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache The cache frontend
	 * @return void
	 */
	public function setCache(\TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);

		$codeOrData = ($cache instanceof \TYPO3\FLOW3\Cache\Frontend\PhpFrontend) ? 'Code' : 'Data';
		$cacheDirectory = $this->environment->getPathToTemporaryDirectory() . 'Cache/' . $codeOrData . '/' . $this->cacheIdentifier . '/';
		if (!is_writable($cacheDirectory)) {
			try {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($cacheDirectory);
			} catch (\TYPO3\FLOW3\Utility\Exception $exception) {
				throw new \TYPO3\FLOW3\Cache\Exception('The cache directory "' . $cacheDirectory . '" could not be created.', 1264426237);
			}
		}
		if (!is_dir($cacheDirectory) && !is_link($cacheDirectory)) throw new \TYPO3\FLOW3\Cache\Exception('The cache directory "' . $cacheDirectory . '" does not exist.', 1203965199);
		if (!is_writable($cacheDirectory)) throw new \TYPO3\FLOW3\Cache\Exception('The cache directory "' . $cacheDirectory . '" is not writable.', 1203965200);

		$this->cacheDirectory = $cacheDirectory;
		$this->cacheEntryFileExtension = ($cache instanceof \TYPO3\FLOW3\Cache\Frontend\PhpFrontend) ? '.php' : '';
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @api
	 */
	public function getCacheDirectory() {
		return $this->cacheDirectory;
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws \TYPO3\FLOW3\Cache\Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!is_string($data)) throw new \TYPO3\FLOW3\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1204481674);
		if ($entryIdentifier !== basename($entryIdentifier)) throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073032);
		if ($entryIdentifier === '') throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1298114280);

		$this->remove($entryIdentifier);

		$temporaryCacheEntryPathAndFilename = $this->cacheDirectory . uniqid() . '.temp';
		if (strlen($temporaryCacheEntryPathAndFilename) > $this->environment->getMaximumPathLength()) {
			throw new \TYPO3\FLOW3\Cache\Exception('The length of the temporary cache file path "' . $temporaryCacheEntryPathAndFilename . '" is ' . strlen($temporaryCacheEntryPathAndFilename) . ' characters long and exceeds the maximum path length of ' . $this->environment->getMaximumPathLength() . '. Please consider setting the temporaryDirectoryBase option to a shorter path. ', 1248710426);
		}

		$expiryTime = ($lifetime === NULL) ? 0 : (time() + $lifetime);
		$metaData = str_pad($expiryTime, self::EXPIRYTIME_LENGTH) . implode(' ', $tags) . str_pad(strlen($data), self::DATASIZE_DIGITS);
		$result = file_put_contents($temporaryCacheEntryPathAndFilename, $data . $metaData);

		if ($result === FALSE) throw new \TYPO3\FLOW3\Cache\Exception('The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.', 1204026251);
		$i = 0;
		$cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		while (!rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename) && $i < 5) {
			$i++;
		}
		if ($result === FALSE) throw new \TYPO3\FLOW3\Cache\Exception('The cache file "' . $cacheEntryPathAndFilename . '" could not be written.', 1222361632);
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @api
	 */
	public function get($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073033);

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if ($this->isCacheFileExpired($pathAndFilename)) {
			return FALSE;
		}
		$dataSize = (integer)file_get_contents($pathAndFilename, NULL, NULL, filesize($pathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
		return file_get_contents($pathAndFilename, NULL, NULL, 0, $dataSize);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function has($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073034);

		return !$this->isCacheFileExpired($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @api
	 */
	public function remove($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073035);
		if ($entryIdentifier === '') throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1298114279);

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if (file_exists($pathAndFilename) === FALSE) return FALSE;
		if (unlink($pathAndFilename) === FALSE) return FALSE;
		return TRUE;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $searchedTag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @api
	 */
	public function findIdentifiersByTag($searchedTag) {
		$entryIdentifiers = array();
		$now = $_SERVER['REQUEST_TIME'];
		$cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
		for($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) continue;

			$cacheEntryPathAndFilename = $directoryIterator->getPathname();
			$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
			$metaData = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index);

			$expiryTime = (integer)substr($metaData, 0, self::EXPIRYTIME_LENGTH);
			if ($expiryTime !== 0 && $expiryTime < $now) continue;
			if (in_array($searchedTag, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
				if ($cacheEntryFileExtensionLength > 0) {
					$entryIdentifiers[] = substr ($directoryIterator->getFilename(), 0, - $cacheEntryFileExtensionLength);
				} else {
					$entryIdentifiers[] = $directoryIterator->getFilename();
				}
			}
		}
		return $entryIdentifiers;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		\TYPO3\FLOW3\Utility\Files::emptyDirectoryRecursively($this->cacheDirectory);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @api
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		if (count($identifiers) === 0) return;

		foreach ($identifiers as $entryIdentifier) {
			$this->remove($entryIdentifier);
		}
	}

	/**
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheEntryPathAndFilename
	 * @return boolean
	 * @api
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		if (file_exists($cacheEntryPathAndFilename) === FALSE) return TRUE;

		$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
		$expiryTime = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index, self::EXPIRYTIME_LENGTH);
		return ($expiryTime != 0 && $expiryTime < time());
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
		for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) continue;

			if ($this->isCacheFileExpired($directoryIterator->getPathname())) {
				$cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
				if ($cacheEntryFileExtensionLength > 0) {
					$this->remove(substr($directoryIterator->getFilename(), 0, - $cacheEntryFileExtensionLength));
				} else {
					$this->remove($directoryIterator->getFilename());
				}
			}
		}
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 * Usually only one cache entry should be found - if more than one exist, this
	 * is due to some error or crash.
	 *
	 * @param string $entryIdentifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @throws \TYPO3\FLOW3\Cache\Exception if no frontend has been set
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		$pattern = $this->cacheDirectory . $entryIdentifier;
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) === 0) return FALSE;
		return $filesFound;
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier (' . $entryIdentifier . ') must not contain a path segment.', 1282073036);
		}

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : require_once($pathAndFilename);
	}
}
?>
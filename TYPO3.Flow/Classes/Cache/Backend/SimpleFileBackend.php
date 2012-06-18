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

use TYPO3\FLOW3\Cache\Frontend\PhpFrontend;
use TYPO3\FLOW3\Cache\Frontend\FrontendInterface;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A caching backend which stores cache entries in files, but does not support or
 * care about expiry times and tags.
 *
 * @api
 * @FLOW3\Proxy(false)
 */
class SimpleFileBackend extends AbstractBackend implements PhpCapableBackendInterface {

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
	 * @var array
	 */
	protected $cacheEntryIdentifiers = array();

	/**
	 * @var boolean
	 */
	protected $frozen = FALSE;

	/**
	 * If the extension "igbinary" is installed, use it for increased performance.
	 * Caching the result of extension_loaded() here is faster than calling extension_loaded() multiple times.
	 *
	 * @var boolean
	 */
	protected $useIgBinary = FALSE;

	/**
	 * Initializes this cache frontend
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->useIgBinary = extension_loaded('igbinary');
	}

	/**
	 * Sets a reference to the cache frontend which uses this backend and
	 * initializes the default cache directory.
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache The cache frontend
	 * @return void
	 * @throws \TYPO3\FLOW3\Cache\Exception
	 */
	public function setCache(FrontendInterface $cache) {
		parent::setCache($cache);

		$codeOrData = ($cache instanceof PhpFrontend) ? 'Code' : 'Data';
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
		$this->cacheEntryFileExtension = ($cache instanceof PhpFrontend) ? '.php' : '';

		if ((strlen($this->cacheDirectory) + 23) > $this->environment->getMaximumPathLength()) {
			throw new \TYPO3\FLOW3\Cache\Exception('The length of the temporary cache path "' . $this->cacheDirectory . '" exceeds the maximum path length of ' . ($this->environment->getMaximumPathLength() - 23). '. Please consider setting the temporaryDirectoryBase option to a shorter path. ', 1248710426);
		}
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
	 * @param array $tags Ignored in this type of cache backend
	 * @param integer $lifetime Ignored in this type of cache backend
	 * @return void
	 * @throws \TYPO3\FLOW3\Cache\Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
	 * @throws \TYPO3\FLOW3\Cache\Exception\InvalidDataException
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!is_string($data)) {
			throw new \TYPO3\FLOW3\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1334756734);
		}
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756735);
		}
		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756736);
		}

		$temporaryCacheEntryPathAndFilename = $this->cacheDirectory . uniqid() . '.temp';
		$result = file_put_contents($temporaryCacheEntryPathAndFilename, $data);
		if ($result === FALSE) {
			throw new \TYPO3\FLOW3\Cache\Exception('The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.', 1334756737);
		}

		$cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename);
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function get($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756877);
		}

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if (!file_exists($pathAndFilename)) {
			return FALSE;
		}
		return file_get_contents($pathAndFilename);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function has($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756878);
		}
		return file_exists($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function remove($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756960);
		}
		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756961);
		}

		try {
			unlink($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
		} catch (\Exception $e) {
			return FALSE;
		}
		return TRUE;
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
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheEntryPathAndFilename
	 * @return boolean
	 * @api
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		return (file_exists($cacheEntryPathAndFilename) === FALSE);
	}

	/**
	 * Not neccessary
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 *
	 * @param string $entryIdentifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @throws \TYPO3\FLOW3\Cache\Exception if no frontend has been set
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		return (file_exists($pathAndFilename) ? array($pathAndFilename) : FALSE);
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier (' . $entryIdentifier . ') must not contain a path segment.', 1282073036);
		}
		return (file_exists($pathAndFilename) ? require_once($pathAndFilename) : FALSE);
	}
}
?>
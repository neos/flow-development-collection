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
 * A caching backend which stores cache entries in files
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class FileBackend extends \F3\FLOW3\Cache\Backend\AbstractBackend implements \F3\FLOW3\Cache\Backend\PhpCapableBackendInterface {

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
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

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
	 * Sets a reference to the cache frontend which uses this backend and
	 * initializes the default cache directory
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCache(\F3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);

		$cacheDirectory = $this->environment->getPathToTemporaryDirectory() . 'Cache/' . $this->cacheIdentifier . '/';
		if (!is_writable($cacheDirectory)) {
			try {
				\F3\FLOW3\Utility\Files::createDirectoryRecursively($cacheDirectory);
			} catch (\F3\FLOW3\Utility\Exception $exception) {
				throw new \F3\FLOW3\Cache\Exception('The cache directory "' . $cacheDirectory . '" could not be created.', 1264426237);
			}
		}
		if (!is_dir($cacheDirectory)) throw new \F3\FLOW3\Cache\Exception('The cache directory "' . $cacheDirectory . '" does not exist.', 1203965199);
		if (!is_writable($cacheDirectory)) throw new \F3\FLOW3\Cache\Exception('The cache directory "' . $cacheDirectory . '" is not writable.', 1203965200);

		$this->cacheDirectory = $cacheDirectory;
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @throws \F3\FLOW3\Cache\Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('No cache frontend has been set yet via setCache().', 1204111375);
		if (!is_string($data)) throw new \F3\FLOW3\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1204481674);

		$this->remove($entryIdentifier);

		$temporaryCacheEntryPathAndFilename = $this->cacheDirectory . uniqid() . '.temp';
		if (strlen($temporaryCacheEntryPathAndFilename) > $this->environment->getMaximumPathLength()) {
			throw new \F3\FLOW3\Cache\Exception('The length of the temporary cache file path "' . $temporaryCacheEntryPathAndFilename . '" is ' . strlen($temporaryCacheEntryPathAndFilename) . ' characters long and exceeds the maximum path length of ' . $this->environment->getMaximumPathLength() . '. Please consider setting the temporaryDirectoryBase option to a shorter path. ', 1248710426);
		}

		$expiryTime = ($lifetime === NULL) ? 0 : (time() + $lifetime);
		$metaData = str_pad($expiryTime, self::EXPIRYTIME_LENGTH) . implode(' ', $tags) . str_pad(strlen($data), self::DATASIZE_DIGITS);
		$result = file_put_contents($temporaryCacheEntryPathAndFilename, $data . $metaData);

		if ($result === FALSE) throw new \F3\FLOW3\Cache\Exception('The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.', 1204026251);
		$i = 0;
		$cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier;
		while (!rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename) && $i < 5) {
			$i++;
		}
		if ($result === FALSE) throw new \F3\FLOW3\Cache\Exception('The cache file "' . $cacheEntryPathAndFilename . '" could not be written.', 1222361632);

		$this->systemLogger->log(sprintf('Cache %s: set entry "%s".', $this->cacheIdentifier, $entryIdentifier), LOG_DEBUG);
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function get($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier;
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function has($entryIdentifier) {
		return !$this->isCacheFileExpired($this->cacheDirectory . $entryIdentifier);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function remove($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier;
		if (!file_exists($pathAndFilename)) return FALSE;
		if (unlink ($pathAndFilename) === FALSE) return FALSE;
		foreach($this->findTagFilesByEntry($entryIdentifier) as $pathAndFilename) {
			if (!file_exists($pathAndFilename)) return FALSE;
			if (unlink ($pathAndFilename) === FALSE) return FALSE;
		}

		$this->systemLogger->log(sprintf('Cache %s: removed entry "%s".', $this->cacheIdentifier, $entryIdentifier), LOG_DEBUG);
		return TRUE;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $searchedTag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTag($searchedTag) {
		$entryIdentifiers = array();
		$now = time();
		for($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) continue;

			$cacheEntryPathAndFilename = $directoryIterator->getPathname();
			$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
			$metaData = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index);

			$expiryTime = (integer)substr($metaData, 0, self::EXPIRYTIME_LENGTH);
			if ($expiryTime !== 0 && $expiryTime < $now) continue;
			if (in_array($searchedTag, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
				$entryIdentifiers[] = $directoryIterator->getFilename();
			}
		}
		return $entryIdentifiers;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function flush() {
		\F3\FLOW3\Utility\Files::emptyDirectoryRecursively($this->cacheDirectory);
		$this->systemLogger->log(sprintf('Cache %s: flushed all entries.', $this->cacheIdentifier), LOG_INFO);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		if (count($identifiers) === 0) return;

		foreach ($identifiers as $entryIdentifier) {
			$this->remove($entryIdentifier);
		}
		$this->systemLogger->log(sprintf('Cache %s: removed %s entries matching tag "%s"', $this->cacheIdentifier, count($identifiers), $tag), LOG_INFO);
	}

	/**
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheEntryPathAndFilename
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		if (!file_exists($cacheEntryPathAndFilename)) return TRUE;

		$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
		$expiryTime = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index, self::EXPIRYTIME_LENGTH);
		return ($expiryTime != 0 && $expiryTime < time());
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function collectGarbage() {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1222686150);

		$pattern = $this->cacheDirectory . 'Data/' . $this->cacheIdentifier . '/*/*/*';
		$filesFound = glob($pattern);
		foreach ($filesFound as $cacheFilename) {
			if ($this->isCacheFileExpired($cacheFilename)) {
				$this->remove(basename($cacheFilename));
			}
		}
		$this->systemLogger->log(sprintf('Cache %s: removed %s files during garbage collection', $this->cacheIdentifier, count($filesFound)), LOG_INFO);
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 * Usually only one cache entry should be found - if more than one exist, this
	 * is due to some error or crash.
	 *
	 * @param string $entryIdentifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Cache\Exception if no frontend has been set
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$pattern = $this->cacheDirectory . $entryIdentifier;
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) === 0) return FALSE;
		return $filesFound;
	}


	/**
	 * Tries to find the tag entries for the specified cache entry.
	 *
	 * @param string $entryIdentifier The cache entry identifier to find tag files for
	 * @return array The file names (including path)
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Cache\Exception if no frontend has been set
	 */
	protected function findTagFilesByEntry($entryIdentifier) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$path = $this->cacheDirectory . 'Tags/';
		$pattern = $path . '*/' . $this->cacheIdentifier . self::SEPARATOR . $entryIdentifier;
		return glob($pattern);
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier;
		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : require_once($pathAndFilename);
	}
}
?>
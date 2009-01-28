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
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 */

/**
 * A caching backend which stores cache entries in files
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class FileBackend extends \F3\FLOW3\Cache\Backend\AbstractBackend {

	const SEPARATOR = '-';

	const FILENAME_EXPIRYTIME_FORMAT = 'YmdHis';
	const FILENAME_EXPIRYTIME_GLOB = '??????????????';
	const FILENAME_EXPIRYTIME_UNLIMITED = '99991231235959';

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
	 * Initializes the default cache directory
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->cacheDirectory === '') {
			$cacheDirectory = $this->environment->getPathToTemporaryDirectory() . 'Cache/';
			try {
				$this->setCacheDirectory($cacheDirectory);
			} catch(\F3\FLOW3\Cache\Exception $exception) {
			}
		}
	}

	/**
	 * Sets the directory where the cache files are stored.
	 *
	 * @param string $cacheDirectory The directory
	 * @return void
	 * @throws \F3\FLOW3\Cache\Exception if the directory does not exist, is not writable or could not be created.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectory($cacheDirectory) {
		if ($cacheDirectory{strlen($cacheDirectory)-1} !== '/') {
			$cacheDirectory .= '/';
		}
		if (!is_writable($cacheDirectory)) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($cacheDirectory);
		}
		if (!is_dir($cacheDirectory)) throw new \F3\FLOW3\Cache\Exception('The directory "' . $cacheDirectory . '" does not exist.', 1203965199);
		if (!is_writable($cacheDirectory)) throw new \F3\FLOW3\Cache\Exception('The directory "' . $cacheDirectory . '" is not writable.', 1203965200);

		$tagsDirectory = $cacheDirectory . $this->context . '/Tags/';
		if (!is_writable($tagsDirectory)) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($tagsDirectory);
		}
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
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws \F3\FLOW3\Cache\Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('No cache frontend has been set yet via setCache().', 1204111375);
		if (!is_string($data)) throw new \F3\FLOW3\Cache\Exception\InvalidData('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1204481674);

		if ($lifetime === 0 || ($lifetime === NULL && $this->defaultLifetime === 0)) {
			$expiryTime = new \DateTime('9999-12-31T23:59:59+0000', new \DateTimeZone('UTC'));
		} else {
			if ($lifetime === NULL) $lifetime = $this->defaultLifetime;
			$expiryTime = new \DateTime('now +' . $lifetime . ' seconds', new \DateTimeZone('UTC'));
		}
		$cacheEntryPath = $this->renderCacheEntryPath($entryIdentifier);
		$filename = $this->renderCacheFilename($entryIdentifier, $expiryTime);

		if (!is_writable($cacheEntryPath)) {
			try {
				\F3\FLOW3\Utility\Files::createDirectoryRecursively($cacheEntryPath);
			} catch(\Exception $exception) {
			}
			if (!is_writable($cacheEntryPath)) throw new \F3\FLOW3\Cache\Exception('The cache directory "' . $cacheEntryPath . '" could not be created.', 1204026250);
		}

		$this->remove($entryIdentifier);

		$temporaryFilename = $filename . '.' . uniqid() . '.temp';
		$result = file_put_contents($cacheEntryPath . $temporaryFilename, $data);
		if ($result === FALSE) throw new \F3\FLOW3\Cache\Exception('The temporary cache file "' . $temporaryFilename . '" could not be written.', 1204026251);
		for ($i=0; $i<5; $i++) {
			$result = rename($cacheEntryPath . $temporaryFilename, $cacheEntryPath . $filename);
			if ($result === TRUE) break;
		}
		if ($result === FALSE) throw new \F3\FLOW3\Cache\Exception('The cache file "' . $filename . '" could not be written.', 1222361632);

		foreach ($tags as $tag) {
			$tagPath = $this->cacheDirectory . $this->context . '/Tags/' . $tag . '/';
			if (!is_writable($tagPath)) {
				mkdir($tagPath);
			}
			touch($tagPath . $this->cache->getIdentifier() . self::SEPARATOR . $entryIdentifier);
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function get($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByIdentifier($entryIdentifier);
		if ($pathsAndFilenames === FALSE) return FALSE;
		$pathAndFilename = array_pop($pathsAndFilenames);
		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : file_get_contents($pathAndFilename);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function has($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByIdentifier($entryIdentifier);
		if ($pathsAndFilenames === FALSE) return FALSE;
		return !$this->isCacheFileExpired(array_pop($pathsAndFilenames));
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByIdentifier($entryIdentifier);
		$this->systemLogger->log(sprintf('Cache %s: removing entry "%s".', $this->cache->getIdentifier(), $entryIdentifier), LOG_DEBUG);
		if ($pathsAndFilenames === FALSE) return FALSE;

		foreach ($pathsAndFilenames as $pathAndFilename) {
			$result = unlink ($pathAndFilename);
			if ($result === FALSE) return FALSE;
		}

		$pathsAndFilenames = $this->findTagFilesByEntry($entryIdentifier);
		if ($pathsAndFilenames === FALSE) return FALSE;

		foreach ($pathsAndFilenames as $pathAndFilename) {
			$result = unlink ($pathAndFilename);
			if ($result === FALSE) return FALSE;
		}
		return TRUE;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTag($tag) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$path = $this->cacheDirectory . $this->context . '/Tags/';
		$pattern = $path . $tag . '/' . $this->cache->getIdentifier() . self::SEPARATOR . '*';
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) == 0) return array();

		$cacheEntries = array();
		foreach ($filesFound as $filename) {
			list(,$entryIdentifier) = explode(self::SEPARATOR, basename($filename));
			if ($this->has($entryIdentifier)) {
				$cacheEntries[$entryIdentifier] = $entryIdentifier;
			}
		}
		return array_values($cacheEntries);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flush() {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$path = $this->cacheDirectory . $this->context . '/Data/' . $this->cache->getIdentifier() . '/';
		$pattern = $path . '*/*/*';
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) == 0) return;

		foreach ($filesFound as $filename) {
			list(,$entryIdentifier) = explode(self::SEPARATOR, basename($filename));
			$this->remove($entryIdentifier);
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		if (count($identifiers) === 0) return;

		$this->systemLogger->log(sprintf('Cache %s: removing %s entries matching tag "%s"', $this->cache->getIdentifier(), count($identifiers), $tag), LOG_INFO);
		foreach ($identifiers as $entryIdentifier) {
			$this->remove($entryIdentifier);
		}
	}

	/**
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheFilename
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function isCacheFileExpired($cacheFilename) {
		list($timestamp) = explode(self::SEPARATOR, basename($cacheFilename), 2);
		return $timestamp < gmdate('YmdHis');
	}

	/**
	 * Does garbage collection for the given entry or all entries.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectGarbage() {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1222686150);

		$pattern = $this->cacheDirectory . $this->context . '/Data/' . $this->cache->getIdentifier() . '/*/*/*';
		$filesFound = glob($pattern);
		foreach ($filesFound as $cacheFile) {
			$splitFilename = explode(self::SEPARATOR, basename($cacheFile), 2);
			if ($splitFilename[0] < gmdate('YmdHis')) {
				$this->remove($splitFilename[1]);
			}
		}
		$this->systemLogger->log(sprintf('Cache %s: removed %s files during garbage collection', $this->cache->getIdentifier(), count($filesFound)), LOG_INFO);
	}

	/**
	 * Renders a file name for the specified cache entry
	 *
	 * @param string $identifier Identifier for the cache entry
	 * @param \DateTime $expiry Date and time specifying the expiration of the entry. Must be a UTC time.
	 * @return string Filename of the cache data file
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function renderCacheFilename($identifier, \DateTime $expiryTime) {
		$filename = $expiryTime->format(self::FILENAME_EXPIRYTIME_FORMAT) . self::SEPARATOR . $identifier;
		return $filename;
	}

	/**
	 * Renders the full path (excluding file name) leading to the given cache entry.
	 * Doesn't check if such a cache entry really exists.
	 *
	 * @param string $identifier Identifier for the cache entry
	 * @return string Absolute path leading to the directory containing the cache entry
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function renderCacheEntryPath($identifier) {
		$identifierHash = sha1($identifier);
		return $this->cacheDirectory . $this->context . '/Data/' . $this->cache->getIdentifier() . '/' . $identifierHash{0} . '/' . $identifierHash{1} . '/';
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 * Usually only one cache entry should be found - if more than one exist, this
	 * is due to some error or crash.
	 *
	 * @param string $identifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Cache\Exception if no frontend has been set
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$pattern = $this->renderCacheEntryPath($entryIdentifier) . self::FILENAME_EXPIRYTIME_GLOB . self::SEPARATOR . $entryIdentifier;
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) == 0) return FALSE;
		return $filesFound;
	}


	/**
	 * Tries to find the tag entries for the specified cache entry.
	 *
	 * @param string $identifier The cache entry identifier to find tag files for
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Cache\Exception if no frontend has been set
	 */
	protected function findTagFilesByEntry($entryIdentifier) {
		if (!$this->cache instanceof \F3\FLOW3\Cache\Frontend\FrontendInterface) throw new \F3\FLOW3\Cache\Exception('Yet no cache frontend has been set via setCache().', 1204111376);

		$path = $this->cacheDirectory . $this->context . '/Tags/';
		$pattern = $path . '*/' . $this->cache->getIdentifier() . self::SEPARATOR . $entryIdentifier;
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) == 0) return FALSE;
		return $filesFound;
	}
}
?>
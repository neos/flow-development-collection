<?php
namespace TYPO3\Flow\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Cache\Exception;
use TYPO3\Flow\Cache\Exception\InvalidDataException;
use TYPO3\Flow\Cache\Frontend\PhpFrontend;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Exception as UtilityException;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\Lock\Lock;
use TYPO3\Flow\Utility\OpcodeCacheHelper;

/**
 * A caching backend which stores cache entries in files, but does not support or
 * care about expiry times and tags.
 *
 * @api
 * @Flow\Proxy(false)
 */
class SimpleFileBackend extends AbstractBackend implements PhpCapableBackendInterface, IterableBackendInterface
{
    const SEPARATOR = '^';

    const EXPIRYTIME_FORMAT = 'YmdHis';
    const EXPIRYTIME_LENGTH = 14;

    const DATASIZE_DIGITS = 10;

    /**
     * Directory where the files are stored.
     *
     * @var string
     */
    protected $cacheDirectory;

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
    protected $frozen = false;

    /**
     * If the extension "igbinary" is installed, use it for increased performance.
     * Caching the result of extension_loaded() here is faster than calling extension_loaded() multiple times.
     *
     * @var boolean
     */
    protected $useIgBinary = false;

    /**
     * @var \DirectoryIterator
     */
    protected $cacheFilesIterator;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Initializes this cache frontend
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->useIgBinary = extension_loaded('igbinary');
    }

    /**
     * @param CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Sets a reference to the cache frontend which uses this backend and
     * initializes the default cache directory.
     *
     * @param FrontendInterface $cache The cache frontend
     * @return void
     * @throws Exception
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);

        $cacheDirectory = $this->cacheDirectory;
        if ($cacheDirectory == '') {
            $codeOrData = ($cache instanceof PhpFrontend) ? 'Code' : 'Data';
            $baseDirectory = ($this->cacheManager->isCachePersistent($cache->getIdentifier()) ? FLOW_PATH_DATA . 'Persistent/' : $this->environment->getPathToTemporaryDirectory());
            $cacheDirectory = $baseDirectory . 'Cache/' . $codeOrData . '/' . $this->cacheIdentifier . '/';
        }
        if (!is_writable($cacheDirectory)) {
            try {
                Files::createDirectoryRecursively($cacheDirectory);
            } catch (UtilityException $exception) {
                throw new Exception('The cache directory "' . $cacheDirectory . '" could not be created.', 1264426237);
            }
        }
        if (!is_dir($cacheDirectory) && !is_link($cacheDirectory)) {
            throw new Exception('The cache directory "' . $cacheDirectory . '" does not exist.', 1203965199);
        }
        if (!is_writable($cacheDirectory)) {
            throw new Exception('The cache directory "' . $cacheDirectory . '" is not writable.', 1203965200);
        }

        $this->cacheDirectory = $cacheDirectory;
        $this->cacheEntryFileExtension = ($cache instanceof PhpFrontend) ? '.php' : '';
    }

    /**
     * Sets the directory where the cache files are stored
     *
     * @param string $cacheDirectory Full path of the cache directory
     * @return void
     * @api
     */
    public function setCacheDirectory($cacheDirectory)
    {
        $this->cacheDirectory = rtrim($cacheDirectory, '/') . '/';
    }

    /**
     * Returns the directory where the cache files are stored
     *
     * @return string Full path of the cache directory
     * @api
     */
    public function getCacheDirectory()
    {
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
     * @throws Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
     * @throws InvalidDataException
     * @throws \InvalidArgumentException
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = null)
    {
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1334756734);
        }
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756735);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756736);
        }

        $cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        $result = $this->writeCacheFile($cacheEntryPathAndFilename, $data);

        if ($result !== false) {
            if ($this->cacheEntryFileExtension === '.php') {
                OpcodeCacheHelper::clearAllActive($cacheEntryPathAndFilename);
            }
            return;
        }

        $this->throwExceptionIfPathExceedsMaximumLength($cacheEntryPathAndFilename);
        throw new Exception('The cache file "' . $cacheEntryPathAndFilename . '" could not be written.', 1334756737);
    }

    /**
     * Loads data from a cache file.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @throws \InvalidArgumentException
     * @api
     */
    public function get($entryIdentifier)
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756877);
        }

        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;

        if (!file_exists($pathAndFilename)) {
            return false;
        }

        $lock = new Lock($pathAndFilename, false);
        $result = file_get_contents($pathAndFilename);
        $lock->release();

        return $result;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier
     * @return boolean TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException
     * @api
     */
    public function has($entryIdentifier)
    {
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
    public function remove($entryIdentifier)
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756960);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756961);
        }

        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;

        try {
            $lock = new Lock($pathAndFilename);
            unlink($pathAndFilename);
            $lock->release();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush()
    {
        Files::emptyDirectoryRecursively($this->cacheDirectory);
    }

    /**
     * Checks if the given cache entry files are still valid or if their
     * lifetime has exceeded.
     *
     * @param string $cacheEntryPathAndFilename
     * @return boolean
     * @api
     */
    protected function isCacheFileExpired($cacheEntryPathAndFilename)
    {
        return (file_exists($cacheEntryPathAndFilename) === false);
    }

    /**
     * Not necessary
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
    }

    /**
     * Tries to find the cache entry for the specified identifier.
     *
     * @param string $entryIdentifier The cache entry identifier
     * @return mixed The filenames (including path) as an array if one or more entries could be found, otherwise FALSE
     * @throws Exception if no frontend has been set
     */
    protected function findCacheFilesByIdentifier($entryIdentifier)
    {
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return (file_exists($pathAndFilename) ? array($pathAndFilename) : false);
    }

    /**
     * Loads PHP code from the cache and include_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     * @throws \InvalidArgumentException
     * @api
     */
    public function requireOnce($entryIdentifier)
    {
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier (' . $entryIdentifier . ') must not contain a path segment.', 1282073036);
        }

        if (is_file($pathAndFilename)) {
            return include_once($pathAndFilename);
        } else {
            return false;
        }
    }

    /**
     * Returns the data of the current cache entry pointed to by the cache entry
     * iterator.
     *
     * @return mixed
     * @api
     */
    public function current()
    {
        if ($this->cacheFilesIterator === null) {
            $this->rewind();
        }

        $pathAndFilename = $this->cacheFilesIterator->getPathname();

        $lock = new Lock($pathAndFilename, false);
        $result = file_get_contents($pathAndFilename);
        $lock->release();
        return $result;
    }

    /**
     * Move forward to the next cache entry
     *
     * @return void
     * @api
     */
    public function next()
    {
        if ($this->cacheFilesIterator === null) {
            $this->rewind();
        }
        $this->cacheFilesIterator->next();
        while ($this->cacheFilesIterator->isDot() && $this->cacheFilesIterator->valid()) {
            $this->cacheFilesIterator->next();
        }
    }

    /**
     * Returns the identifier of the current cache entry pointed to by the cache
     * entry iterator.
     *
     * @return string
     * @api
     */
    public function key()
    {
        if ($this->cacheFilesIterator === null) {
            $this->rewind();
        }
        return $this->cacheFilesIterator->getBasename($this->cacheEntryFileExtension);
    }

    /**
     * Checks if the current position of the cache entry iterator is valid
     *
     * @return boolean TRUE if the current position is valid, otherwise FALSE
     * @api
     */
    public function valid()
    {
        if ($this->cacheFilesIterator === null) {
            $this->rewind();
        }
        return $this->cacheFilesIterator->valid();
    }

    /**
     * Rewinds the cache entry iterator to the first element
     *
     * @return void
     * @api
     */
    public function rewind()
    {
        if ($this->cacheFilesIterator === null) {
            $this->cacheFilesIterator = new \DirectoryIterator($this->cacheDirectory);
        }
        $this->cacheFilesIterator->rewind();
        while (substr($this->cacheFilesIterator->getFilename(), 0, 1) === '.' && $this->cacheFilesIterator->valid()) {
            $this->cacheFilesIterator->next();
        }
    }

    /**
     * @param string $cacheEntryPathAndFilename
     * @return void
     * @throws Exception
     */
    protected function throwExceptionIfPathExceedsMaximumLength($cacheEntryPathAndFilename)
    {
        if (strlen($cacheEntryPathAndFilename) > $this->environment->getMaximumPathLength()) {
            throw new Exception('The length of the cache entry path "' . $cacheEntryPathAndFilename . '" exceeds the maximum path length of ' . $this->environment->getMaximumPathLength() . '. Please consider setting the FLOW_PATH_TEMPORARY_BASE environment variable to a shorter path. ', 1248710426);
        }
    }

    /**
     * Writes the cache data into the given cache file, using locking.
     *
     * @param string $cacheEntryPathAndFilename
     * @param string $data
     * @return boolean|integer Return value of file_put_contents
     */
    protected function writeCacheFile($cacheEntryPathAndFilename, $data)
    {
        $lock = new Lock($cacheEntryPathAndFilename);
        $result = file_put_contents($cacheEntryPathAndFilename, $data);
        $lock->release();

        return $result;
    }
}

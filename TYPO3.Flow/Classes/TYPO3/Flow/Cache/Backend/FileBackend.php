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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Exception;
use TYPO3\Flow\Cache\Exception\InvalidDataException;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\Lock\Lock;
use TYPO3\Flow\Utility\OpcodeCacheHelper;

/**
 * A caching backend which stores cache entries in files
 *
 * @api
 * @Flow\Proxy(false)
 */
class FileBackend extends SimpleFileBackend implements PhpCapableBackendInterface, FreezableBackendInterface, TaggableBackendInterface
{
    const SEPARATOR = '^';

    const EXPIRYTIME_FORMAT = 'YmdHis';
    const EXPIRYTIME_LENGTH = 14;

    const DATASIZE_DIGITS = 10;

    /**
     * A file extension to use for each cache entry.
     *
     * @var string
     */
    protected $cacheEntryFileExtension = '';

    /**
     * @var array
     */
    protected $cacheEntryIdentifiers = [];

    /**
     * @var boolean
     */
    protected $frozen = false;

    /**
     * Freezes this cache backend.
     *
     * All data in a frozen backend remains unchanged and methods which try to add
     * or modify data result in an exception thrown. Possible expiry times of
     * individual cache entries are ignored.
     *
     * On the positive side, a frozen cache backend is much faster on read access.
     * A frozen backend can only be thawed by calling the flush() method.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function freeze()
    {
        if ($this->frozen === true) {
            throw new \RuntimeException(sprintf('The cache "%s" is already frozen.', $this->cacheIdentifier), 1323353176);
        }

        $cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);

        for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if ($directoryIterator->isDot()) {
                continue;
            }
            if ($cacheEntryFileExtensionLength > 0) {
                $entryIdentifier = substr($directoryIterator->getFilename(), 0, -$cacheEntryFileExtensionLength);
            } else {
                $entryIdentifier = $directoryIterator->getFilename();
            }
            $this->cacheEntryIdentifiers[$entryIdentifier] = true;

            $cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
            $lock = new Lock($cacheEntryPathAndFilename);
            file_put_contents($cacheEntryPathAndFilename, $this->internalGet($entryIdentifier, false));
            $lock->release();
        }

        $cachePathAndFileName = $this->cacheDirectory . 'FrozenCache.data';
        $lock = new Lock($cachePathAndFileName);
        if ($this->useIgBinary === true) {
            file_put_contents($cachePathAndFileName, igbinary_serialize($this->cacheEntryIdentifiers));
        } else {
            file_put_contents($cachePathAndFileName, serialize($this->cacheEntryIdentifiers));
        }
        $lock->release();

        $this->frozen = true;
    }

    /**
     * Tells if this backend is frozen.
     *
     * @return boolean
     */
    public function isFrozen()
    {
        return $this->frozen;
    }

    /**
     * Sets a reference to the cache frontend which uses this backend and
     * initializes the default cache directory.
     *
     * This method also detects if this backend is frozen and sets the internal
     * flag accordingly.
     *
     * @param FrontendInterface $cache The cache frontend
     * @return void
     * @throws Exception
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);

        if (file_exists($this->cacheDirectory . 'FrozenCache.data')) {
            $this->frozen = true;
            $cachePathAndFileName = $this->cacheDirectory . 'FrozenCache.data';
            $lock = new Lock($cachePathAndFileName, false);
            $data = file_get_contents($cachePathAndFileName);
            $lock->release();
            if ($this->useIgBinary === true) {
                $this->cacheEntryIdentifiers = igbinary_unserialize($data);
            } else {
                $this->cacheEntryIdentifiers = unserialize($data);
            }
        }
    }

    /**
     * Saves data in a cache file.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws \RuntimeException
     * @throws InvalidDataException
     * @throws Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
     * @throws \InvalidArgumentException
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1204481674);
        }
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073032);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1298114280);
        }
        if ($this->frozen === true) {
            throw new \RuntimeException(sprintf('Cannot add or modify cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344192);
        }

        $cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        $lifetime = $lifetime === null ? $this->defaultLifetime : $lifetime;
        $expiryTime = ($lifetime === 0) ? 0 : (time() + $lifetime);
        $metaData = str_pad($expiryTime, self::EXPIRYTIME_LENGTH) . implode(' ', $tags) . str_pad(strlen($data), self::DATASIZE_DIGITS);

        $result = $this->writeCacheFile($cacheEntryPathAndFilename, $data . $metaData);

        if ($result !== false) {
            if ($this->cacheEntryFileExtension === '.php') {
                OpcodeCacheHelper::clearAllActive($cacheEntryPathAndFilename);
            }
            return;
        }

        $this->throwExceptionIfPathExceedsMaximumLength($cacheEntryPathAndFilename);
        throw new Exception('The cache file "' . $cacheEntryPathAndFilename . '" could not be written.', 1222361632);
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
        return $this->internalGet($entryIdentifier);
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
        if ($this->frozen === true) {
            return isset($this->cacheEntryIdentifiers[$entryIdentifier]);
        }
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073034);
        }
        return !$this->isCacheFileExpired($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @api
     */
    public function remove($entryIdentifier)
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073035);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1298114279);
        }
        if ($this->frozen === true) {
            throw new \RuntimeException(sprintf('Cannot remove cache entry because the backend of cache "%s" is frozen.', $this->cacheIdentifier), 1323344193);
        }

        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if (file_exists($pathAndFilename) === false) {
            return false;
        }
        if (unlink($pathAndFilename) === false) {
            return false;
        }
        return true;
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $searchedTag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag($searchedTag)
    {
        $entryIdentifiers = [];
        $now = $_SERVER['REQUEST_TIME'];
        $cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
        for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if ($directoryIterator->isDot()) {
                continue;
            }

            $cacheEntryPathAndFilename = $directoryIterator->getPathname();
            $lock = new Lock($cacheEntryPathAndFilename, false);
            $index = (integer)file_get_contents($cacheEntryPathAndFilename, null, null, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
            $metaData = file_get_contents($cacheEntryPathAndFilename, null, null, $index);
            $lock->release();

            $expiryTime = (integer)substr($metaData, 0, self::EXPIRYTIME_LENGTH);
            if ($expiryTime !== 0 && $expiryTime < $now) {
                continue;
            }
            if (in_array($searchedTag, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
                if ($cacheEntryFileExtensionLength > 0) {
                    $entryIdentifiers[] = substr($directoryIterator->getFilename(), 0, -$cacheEntryFileExtensionLength);
                } else {
                    $entryIdentifiers[] = $directoryIterator->getFilename();
                }
            }
        }
        return $entryIdentifiers;
    }

    /**
     * Removes all cache entries of this cache and sets the frozen flag to FALSE.
     *
     * @return void
     * @api
     */
    public function flush()
    {
        Files::emptyDirectoryRecursively($this->cacheDirectory);
        if ($this->frozen === true) {
            @unlink($this->cacheDirectory . 'FrozenCache.data');
            $this->frozen = false;
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        if (count($identifiers) === 0) {
            return 0;
        }

        foreach ($identifiers as $entryIdentifier) {
            $this->remove($entryIdentifier);
        }
        return count($identifiers);
    }

    /**
     * Checks if the given cache entry files are still valid or if their
     * lifetime has exceeded.
     *
     * @param string $cacheEntryPathAndFilename
     * @param boolean $acquireLock
     * @return boolean
     * @api
     */
    protected function isCacheFileExpired($cacheEntryPathAndFilename, $acquireLock = true)
    {
        if (file_exists($cacheEntryPathAndFilename) === false) {
            return true;
        }

        if ($acquireLock) {
            $lock = new Lock($cacheEntryPathAndFilename, false);
        }
        $cacheData = file_get_contents($cacheEntryPathAndFilename);
        if ($acquireLock) {
            $lock->release();
        }
        $index = (integer)substr($cacheData, -(self::DATASIZE_DIGITS));
        $expiryTime = (integer)substr($cacheData, $index, (self::EXPIRYTIME_LENGTH));

        return ($expiryTime !== 0 && $expiryTime < time());
    }

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
        if ($this->frozen === true) {
            return;
        }

        for ($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
            if ($directoryIterator->isDot()) {
                continue;
            }

            if ($this->isCacheFileExpired($directoryIterator->getPathname())) {
                $this->remove($directoryIterator->getBasename($this->cacheEntryFileExtension));
            }
        }
    }

    /**
     * Tries to find the cache entry for the specified identifier.
     * Usually only one cache entry should be found - if more than one exist, this
     * is due to some error or crash.
     *
     * @param string $entryIdentifier The cache entry identifier
     * @return mixed The filenames (including path) as an array if one or more entries could be found, otherwise FALSE
     * @throws Exception if no frontend has been set
     */
    protected function findCacheFilesByIdentifier($entryIdentifier)
    {
        $pattern = $this->cacheDirectory . $entryIdentifier;
        $filesFound = glob($pattern);
        if ($filesFound === false || count($filesFound) === 0) {
            return false;
        }
        return $filesFound;
    }

    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @throws \InvalidArgumentException
     * @return mixed Potential return value from the include operation
     * @api
     */
    public function requireOnce($entryIdentifier)
    {
        if ($this->frozen === true) {
            if (isset($this->cacheEntryIdentifiers[$entryIdentifier])) {
                return require_once($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
            } else {
                return false;
            }
        } else {
            $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
            if ($entryIdentifier !== basename($entryIdentifier)) {
                throw new \InvalidArgumentException('The specified entry identifier (' . $entryIdentifier . ') must not contain a path segment.', 1282073036);
            }
            return ($this->isCacheFileExpired($pathAndFilename)) ? false : require_once($pathAndFilename);
        }
    }

    /**
     * Internal get method, allows to nest locks by using the $acquireLock flag
     *
     * @param string $entryIdentifier
     * @param boolean $acquireLock
     * @return bool|string
     * @throws \InvalidArgumentException
     */
    protected function internalGet($entryIdentifier, $acquireLock = true)
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073033);
        }

        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if ($this->frozen === true) {
            if ($acquireLock) {
                $lock = new Lock($pathAndFilename, false);
            }
            $result = (isset($this->cacheEntryIdentifiers[$entryIdentifier]) ? file_get_contents($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension) : false);
            if ($acquireLock) {
                $lock->release();
            }
            return $result;
        }

        if ($this->isCacheFileExpired($pathAndFilename, $acquireLock)) {
            return false;
        }
        if ($acquireLock) {
            $lock = new Lock($pathAndFilename, false);
        }
        $cacheData = file_get_contents($pathAndFilename);
        if ($acquireLock) {
            $lock->release();
        }

        $dataSize = (integer)substr($cacheData, -(self::DATASIZE_DIGITS));
        return substr($cacheData, 0, $dataSize);
    }
}

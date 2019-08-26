<?php
declare(strict_types=1);

namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\AbstractBackend as IndependentAbstractBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Utility\Files;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\OpcodeCacheHelper;

/**
 * A caching backend which stores cache entries in files, but does not support or
 * care about expiry times and tags.
 *
 * @api
 */
class SimpleFileBackend extends IndependentAbstractBackend implements PhpCapableBackendInterface, IterableBackendInterface, WithSetupInterface, WithStatusInterface
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
    protected $cacheDirectory = '';

    /**
     * A file extension to use for each cache entry.
     *
     * @var string
     */
    protected $cacheEntryFileExtension = '';

    /**
     * @var string[]
     */
    protected $cacheEntryIdentifiers = [];

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
     * Overrides the base directory for this cache,
     * the effective directory will be a subdirectory of this.
     * If not given this will be determined by the EnvironmentConfiguration
     *
     * @var string
     */
    protected $baseDirectory;

    /**
     * {@inheritdoc}
     */
    public function __construct(EnvironmentConfiguration $environmentConfiguration, array $options = [])
    {
        parent::__construct($environmentConfiguration, $options);
        $this->useIgBinary = extension_loaded('igbinary');
    }

    /**
     * Sets a reference to the cache frontend which uses this backend and
     * initializes the default cache directory.
     *
     * @param \Neos\Cache\Frontend\FrontendInterface $cache The cache frontend
     * @return void
     * @throws Exception
     */
    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);
        $this->cacheEntryFileExtension = ($cache instanceof PhpFrontend) ? '.php' : '';
        $this->configureCacheDirectory();
    }

    /**
     * Sets the directory where the cache files are stored
     *
     * @param string $cacheDirectory Full path of the cache directory
     * @return void
     * @api
     */
    public function setCacheDirectory(string $cacheDirectory): void
    {
        $this->cacheDirectory = rtrim($cacheDirectory, '/') . '/';
    }

    /**
     * Returns the directory where the cache files are stored
     *
     * @return string Full path of the cache directory
     * @api
     */
    public function getCacheDirectory(): string
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
     * @throws \InvalidArgumentException
     * @api
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
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
     * @return mixed The cache entry's content as a string or false if the cache entry could not be loaded
     * @throws \InvalidArgumentException
     * @api
     */
    public function get(string $entryIdentifier)
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756877);
        }

        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;

        if (!file_exists($pathAndFilename)) {
            return false;
        }

        return $this->readCacheFile($pathAndFilename);
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier
     * @return boolean true if such an entry exists, false if not
     * @throws \InvalidArgumentException
     * @api
     */
    public function has(string $entryIdentifier): bool
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756878);
        }
        return file_exists($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
    }

    /**
     * Try to remove a file and make sure it is not locked.
     *
     * @param string $fileName The full filename of the file to remove.
     * @return bool True if the file was removed successfully or false otherwise
     */
    private function tryRemoveWithLock(string $fileName): bool
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
            // On Windows, unlinking a locked/opened file will not work, so we just attempt the delete straight away.
            // In the worst case, the unlink will just fail due to concurrent access and the caller needs to deal with that.
            return unlink($fileName);
        }
        $file = fopen($fileName, 'rb');
        if ($file === false) {
            return false;
        }

        $result = false;
        if (flock($file, LOCK_EX) !== false) {
            $result = unlink($fileName);
            flock($file, LOCK_UN);
        }
        fclose($file);

        return $result;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean true if (at least) an entry could be removed or false if no entry was found
     * @throws \InvalidArgumentException
     * @api
     */
    public function remove(string $entryIdentifier): bool
    {
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756960);
        }
        if ($entryIdentifier === '') {
            throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756961);
        }
        $cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        for ($i = 0; $i < 3; $i++) {
            try {
                $result = $this->tryRemoveWithLock($cacheEntryPathAndFilename);

                if ($result === true) {
                    clearstatcache(true, $cacheEntryPathAndFilename);
                    return $result;
                }
            } catch (\Exception $e) {
            }
            usleep(rand(10, 500));
        }

        return false;
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @throws FilesException
     * @api
     */
    public function flush(): void
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
    protected function isCacheFileExpired(string $cacheEntryPathAndFilename): bool
    {
        return (file_exists($cacheEntryPathAndFilename) === false);
    }

    /**
     * Not necessary
     *
     * @return void
     * @api
     */
    public function collectGarbage(): void
    {
    }

    /**
     * Tries to find the cache entry for the specified identifier.
     *
     * @param string $entryIdentifier The cache entry identifier
     * @return mixed The filenames (including path) as an array if one or more entries could be found, otherwise false
     */
    protected function findCacheFilesByIdentifier(string $entryIdentifier)
    {
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        return (file_exists($pathAndFilename) ? [$pathAndFilename] : false);
    }

    /**
     * Loads PHP code from the cache and include_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     * @throws \InvalidArgumentException
     * @api
     */
    public function requireOnce(string $entryIdentifier)
    {
        $pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
        if ($entryIdentifier !== basename($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified entry identifier (' . $entryIdentifier . ') must not contain a path segment.', 1282073036);
        }

        if (is_file($pathAndFilename)) {
            return include_once($pathAndFilename);
        }
        return false;
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
        return $this->readCacheFile($pathAndFilename);
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
    public function key(): string
    {
        if ($this->cacheFilesIterator === null) {
            $this->rewind();
        }
        return $this->cacheFilesIterator->getBasename($this->cacheEntryFileExtension);
    }

    /**
     * Checks if the current position of the cache entry iterator is valid
     *
     * @return boolean true if the current position is valid, otherwise false
     * @api
     */
    public function valid(): bool
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
    protected function throwExceptionIfPathExceedsMaximumLength(string $cacheEntryPathAndFilename): void
    {
        if (strlen($cacheEntryPathAndFilename) > $this->environmentConfiguration->getMaximumPathLength()) {
            throw new Exception('The length of the cache entry path "' . $cacheEntryPathAndFilename . '" exceeds the maximum path length of ' . $this->environmentConfiguration->getMaximumPathLength() . '. Please consider setting the FLOW_PATH_TEMPORARY_BASE environment variable to a shorter path. ', 1248710426);
        }
    }

    /**
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDirectory;
    }

    /**
     * @param string $baseDirectory
     */
    public function setBaseDirectory(string $baseDirectory): void
    {
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * @throws Exception
     */
    protected function configureCacheDirectory(): void
    {
        $cacheDirectory = $this->cacheDirectory;
        if ($cacheDirectory === '') {
            $codeOrData = ($this->cache instanceof PhpFrontend) ? 'Code' : 'Data';
            $baseDirectory = ($this->baseDirectory ?: $this->environmentConfiguration->getFileCacheBasePath());
            $cacheDirectory = $baseDirectory . 'Cache/' . $codeOrData . '/' . $this->cacheIdentifier . '/';
        }

        if (!is_writable($cacheDirectory)) {
            try {
                Files::createDirectoryRecursively($cacheDirectory);
            } catch (FilesException $exception) {
                throw new Exception('The cache directory "' . $cacheDirectory . '" could not be created.', 1264426237);
            }
        }

        $this->cacheDirectory = $cacheDirectory;
        $this->verifyCacheDirectory();
    }

    /**
     * @throws Exception
     */
    protected function verifyCacheDirectory(): void
    {
        if (!is_dir($this->cacheDirectory) && !is_link($this->cacheDirectory)) {
            throw new Exception('The cache directory "' . $this->cacheDirectory . '" does not exist.', 1203965199);
        }
        if (!is_writable($this->cacheDirectory)) {
            throw new Exception('The cache directory "' . $this->cacheDirectory . '" is not writable.', 1203965200);
        }
    }

    /**
     * Reads the cache data from the given cache file, using locking.
     *
     * @param string $cacheEntryPathAndFilename
     * @param int|null $offset
     * @param int|null $maxlen
     * @return boolean|string The contents of the cache file or false on error
     */
    protected function readCacheFile(string $cacheEntryPathAndFilename, int $offset = null, int $maxlen = null)
    {
        for ($i = 0; $i < 3; $i++) {
            $data = false;
            try {
                $file = fopen($cacheEntryPathAndFilename, 'rb');
                if ($file === false) {
                    continue;
                }
                if (flock($file, LOCK_SH) !== false) {
                    if ($offset !== null) {
                        fseek($file, $offset);
                    }
                    $data = fread($file, $maxlen !== null ? $maxlen : filesize($cacheEntryPathAndFilename) - (int)$offset);
                    flock($file, LOCK_UN);
                }
                fclose($file);
            } catch (\Exception $e) {
            }

            if ($data !== false) {
                return $data;
            }
            usleep(rand(10, 500));
        }

        return false;
    }

    /**
     * Writes the cache data into the given cache file, using locking.
     *
     * @param string $cacheEntryPathAndFilename
     * @param string $data
     * @return boolean|integer Return value of file_put_contents
     */
    protected function writeCacheFile(string $cacheEntryPathAndFilename, string $data)
    {
        for ($i = 0; $i < 3; $i++) {
            // This can be replaced by a simple file_put_contents($cacheEntryPathAndFilename, $data, LOCK_EX) once vfs
            // is fixed for file_put_contents with LOCK_EX, see https://github.com/mikey179/vfsStream/wiki/Known-Issues
            $result = false;
            try {
                $file = fopen($cacheEntryPathAndFilename, 'wb');
                if ($file === false) {
                    continue;
                }
                if (flock($file, LOCK_EX) !== false) {
                    $result = fwrite($file, $data);
                    flock($file, LOCK_UN);
                }
                fclose($file);
            } catch (\Exception $e) {
            }
            if ($result !== false) {
                clearstatcache(true, $cacheEntryPathAndFilename);
                return $result;
            }
            usleep(rand(10, 500));
        }

        return false;
    }

    /**
     * Sets up this backend by creating the required cache directory if it doesn't exist yet
     *
     * @return Result
     * @api
     */
    public function setup(): Result
    {
        $result = new Result();
        try {
            $this->configureCacheDirectory();
        } catch (Exception $exception) {
            $result->addError(new Error('Failed to configure cache directory: %s', $exception->getCode(), [$exception->getMessage()], 'Cache Directory'));
        }
        return $result;
    }

    /**
     * Validates that the configured cache directory exists and is writeable and returns some details about its configuration if that's the case
     *
     * @return Result
     * @api
     */
    public function getStatus(): Result
    {
        $result = new Result();
        try {
            $this->verifyCacheDirectory();
        } catch (Exception $exception) {
            $result->addError(new Error($exception->getMessage(), $exception->getCode(), [], 'Cache Directory'));
            return $result;
        }
        $result->addNotice(new Notice($this->baseDirectory ?? '-', null, [], 'Base Directory'));
        $result->addNotice(new Notice($this->getCacheDirectory(), null, [], 'Cache Directory'));
        return $result;
    }
}

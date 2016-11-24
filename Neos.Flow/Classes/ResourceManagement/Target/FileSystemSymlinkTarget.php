<?php
namespace Neos\Flow\ResourceManagement\Target;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\Collection;
use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\Storage\PackageStorage;
use Neos\Utility\Files;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\Flow\ResourceManagement\Target\Exception as TargetException;

/**
 * A target which publishes resources by creating symlinks.
 */
class FileSystemSymlinkTarget extends FileSystemTarget
{
    /**
     * @var boolean
     */
    protected $relativeSymlinks = false;

    /**
     * Publishes the whole collection to this target
     *
     * @param CollectionInterface $collection The collection to publish
     * @param callable $callback Function called after each resource publishing
     * @return void
     */
    public function publishCollection(CollectionInterface $collection, callable $callback = null)
    {
        $storage = $collection->getStorage();
        if ($storage instanceof PackageStorage) {
            foreach ($storage->getPublicResourcePaths() as $packageKey => $path) {
                $this->publishDirectory($path, $packageKey);
            }
        } else {
            parent::publishCollection($collection, $callback);
        }
    }

    /**
     * Publishes the given source stream to this target, with the given relative path.
     *
     * @param resource $sourceStream Stream of the source to publish
     * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
     * @throws TargetException
     * @throws \Exception
     */
    protected function publishFile($sourceStream, $relativeTargetPathAndFilename)
    {
        $pathInfo = UnicodeFunctions::pathinfo($relativeTargetPathAndFilename);
        if (isset($pathInfo['extension']) && array_key_exists(strtolower($pathInfo['extension']), $this->extensionBlacklist) && $this->extensionBlacklist[strtolower($pathInfo['extension'])] === true) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the filename extension "%s" is blacklisted.', $sourceStream, $this->name, strtolower($pathInfo['extension'])), 1447152230);
        }

        $streamMetaData = stream_get_meta_data($sourceStream);

        if ($streamMetaData['wrapper_type'] !== 'plainfile' || $streamMetaData['stream_type'] !== 'STDIO') {
            throw new TargetException(sprintf('Could not publish stream "%s" into resource publishing target "%s" because the source is not a local file.', $streamMetaData['uri'], $this->name), 1416242392);
        }

        $sourcePathAndFilename = $streamMetaData['uri'];
        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

        if (@stat($sourcePathAndFilename) === false) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file is not accessible (file stat failed).', $sourcePathAndFilename, $this->name), 1415716366);
        }

        if (!file_exists(dirname($targetPathAndFilename))) {
            Files::createDirectoryRecursively(dirname($targetPathAndFilename));
        }

        try {
            if (Files::is_link($targetPathAndFilename)) {
                Files::unlink($targetPathAndFilename);
            }

            if ($this->relativeSymlinks) {
                $result = Files::createRelativeSymlink($sourcePathAndFilename, $targetPathAndFilename);
            } else {
                $temporaryTargetPathAndFilename = uniqid($targetPathAndFilename . '.') . '.tmp';
                symlink($sourcePathAndFilename, $temporaryTargetPathAndFilename);
                $result = rename($temporaryTargetPathAndFilename, $targetPathAndFilename);
            }
        } catch (\Exception $exception) {
            $result = false;
        }
        if ($result === false) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file could not be symlinked at target location.', $sourcePathAndFilename, $this->name), 1415716368, (isset($exception) ? $exception : null));
        }

        $this->systemLogger->log(sprintf('FileSystemSymlinkTarget: Published file. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename), LOG_DEBUG);
    }

    /**
     * Publishes the specified directory to this target, with the given relative path.
     *
     * @param string $sourcePath Absolute path to the source directory
     * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
     * @throws TargetException
     * @return void
     */
    protected function publishDirectory($sourcePath, $relativeTargetPathAndFilename)
    {
        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

        if (@stat($sourcePath) === false) {
            throw new TargetException(sprintf('Could not publish directory "%s" into resource publishing target "%s" because the source is not accessible (file stat failed).', $sourcePath, $this->name), 1416244512);
        }

        if (!file_exists(dirname($targetPathAndFilename))) {
            Files::createDirectoryRecursively(dirname($targetPathAndFilename));
        }

        try {
            if (Files::is_link($targetPathAndFilename)) {
                Files::unlink($targetPathAndFilename);
            }
            if ($this->relativeSymlinks) {
                $result = Files::createRelativeSymlink($sourcePath, $targetPathAndFilename);
            } else {
                $temporaryTargetPathAndFilename = uniqid($targetPathAndFilename . '.') . '.tmp';
                symlink($sourcePath, $temporaryTargetPathAndFilename);
                $result = rename($temporaryTargetPathAndFilename, $targetPathAndFilename);
            }
        } catch (\Exception $exception) {
            $result = false;
        }
        if ($result === false) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source directory could not be symlinked at target location.', $sourcePath, $this->name), 1416244515, (isset($exception) ? $exception : null));
        }

        $this->systemLogger->log(sprintf('FileSystemSymlinkTarget: Published directory. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename), LOG_DEBUG);
    }

    /**
     * Set an option value and return if it was set.
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    protected function setOption($key, $value)
    {
        if ($key === 'relativeSymlinks') {
            $this->relativeSymlinks = (boolean)$value;
            return true;
        }

        return parent::setOption($key, $value);
    }
}

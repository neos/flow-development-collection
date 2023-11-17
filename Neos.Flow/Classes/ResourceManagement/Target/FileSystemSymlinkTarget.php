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

use Neos\Error\Messages\Error;
use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\Storage\PackageStorage;
use Neos\Flow\ResourceManagement\Target\Exception as TargetException;
use Neos\Flow\Utility\Algorithms;
use Neos\Utility\Files;

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
     * @return void
     */
    public function publishCollection(CollectionInterface $collection)
    {
        $storage = $collection->getStorage();
        if ($storage instanceof PackageStorage) {
            $iteration = 0;
            foreach ($storage->getPublicResourcePaths() as $packageKey => $path) {
                $this->publishDirectory($path, $packageKey);
                $this->invokeOnPublishCallbacks($iteration, new SymlinkedFolderResourceObject($path));
                $iteration++;
            }
        } else {
            parent::publishCollection($collection);
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
        $extension = strtolower(pathinfo($relativeTargetPathAndFilename, PATHINFO_EXTENSION));
        if ($extension !== '' && array_key_exists($extension, $this->excludedExtensions) && $this->excludedExtensions[$extension] === true) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the filename extension "%s" is excluded.', $sourceStream, $this->name, $extension), 1447152230);
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

        [$result, $exception] = $this->publish($targetPathAndFilename, $sourcePathAndFilename);
        if ($result === false) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file could not be symlinked at target location.', $sourcePathAndFilename, $this->name), 1415716368, ($exception ?? null));
        }

        $this->logger->debug(sprintf('FileSystemSymlinkTarget: Published file. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename));
    }

    /**
     * Removes the specified target file from the public directory
     *
     * This method fails silently if the given file could not be unpublished or already didn't exist anymore.
     *
     * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
     * @return void
     */
    protected function unpublishFile($relativeTargetPathAndFilename)
    {
        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;
        if (!is_link($targetPathAndFilename) && !file_exists($targetPathAndFilename)) {
            $message = sprintf('Did not remove file %s because it did not exist.', $targetPathAndFilename);
            $this->messageCollector->append($message, Error::SEVERITY_NOTICE);
            return;
        }
        if (!Files::unlink($targetPathAndFilename)) {
            $message = sprintf('Removal of file %s failed.', $targetPathAndFilename);
            $this->messageCollector->append($message, Error::SEVERITY_WARNING);
            return;
        }
        Files::removeEmptyDirectoriesOnPath(dirname($targetPathAndFilename));
    }

    /**
     * Publishes the specified directory to this target, with the given relative path.
     *
     * @param string $sourcePath Absolute path to the source directory
     * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
     * @return void
     * @throws TargetException
     */
    protected function publishDirectory($sourcePath, $relativeTargetPathAndFilename)
    {
        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

        if (@stat($sourcePath) === false) {
            throw new TargetException(sprintf('Could not publish directory "%s" into resource publishing target "%s" because the source is not accessible (file stat failed).', $sourcePath, $this->name), 1416244512);
        }

        [$result, $exception] = $this->publish($targetPathAndFilename, $sourcePath);
        if ($result === false) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source directory could not be symlinked at target location.', $sourcePath, $this->name), 1416244515, ($exception ?? null));
        }

        $this->logger->debug(sprintf('FileSystemSymlinkTarget: Published directory. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename));
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

    private function publish(string $targetPathAndFilename, string $sourcePathAndFilename): array
    {
        $exception = null;

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
                $temporaryTargetPathAndFilename = $targetPathAndFilename . '.' . Algorithms::generateRandomString(13) . '.tmp';
                symlink($sourcePathAndFilename, $temporaryTargetPathAndFilename);
                $result = rename($temporaryTargetPathAndFilename, $targetPathAndFilename);
            }
        } catch (\Exception $exception) {
            $result = false;
        }

        if (Files::is_link($targetPathAndFilename) && realpath($targetPathAndFilename) === realpath($sourcePathAndFilename)) {
            $this->logger->debug(sprintf('FileSystemSymlinkTarget: File already published, probably a concurrent write. (target: %s, file: %s)', $this->name, $targetPathAndFilename));
            return [true, null];
        }

        return [$result, $exception];
    }
}

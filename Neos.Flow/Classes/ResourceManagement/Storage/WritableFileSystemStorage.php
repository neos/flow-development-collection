<?php
namespace Neos\Flow\ResourceManagement\Storage;

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
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Storage\Exception as StorageException;
use Neos\Utility\Files;

/**
 * A resource storage based on the (local) file system
 */
class WritableFileSystemStorage extends FileSystemStorage implements WritableStorageInterface
{
    /**
     * Initializes this resource storage
     *
     * @return void
     * @throws StorageException
     */
    public function initializeObject()
    {
        if (!is_writable($this->path)) {
            Files::createDirectoryRecursively($this->path);
        }
        if (!is_dir($this->path) && !is_link($this->path)) {
            throw new StorageException('The directory "' . $this->path . '" which was configured as a resource storage does not exist.', 1361533189);
        }
        if (!is_writable($this->path)) {
            throw new StorageException('The directory "' . $this->path . '" which was configured as a resource storage is not writable.', 1361533190);
        }
    }

    /**
     * Imports a resource (file) from the given URI or PHP resource stream into this storage.
     *
     * On a successful import this method returns a PersistentResource object representing the newly imported persistent resource.
     *
     * @param string | resource $source The URI (or local path and filename) or the PHP resource stream to import the resource from
     * @param string $collectionName Name of the collection the new PersistentResource belongs to
     * @throws StorageException
     * @return PersistentResource A resource object representing the imported resource
     */
    public function importResource($source, $collectionName)
    {
        $temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('Neos_Flow_ResourceImport_');

        if (is_resource($source)) {
            try {
                $target = fopen($temporaryTargetPathAndFilename, 'wb');
                stream_copy_to_stream($source, $target);
                fclose($target);
            } catch (\Exception $exception) {
                throw new StorageException(sprintf('Could import the content stream to temporary file "%s".', $temporaryTargetPathAndFilename), 1380880079);
            }
        } else {
            try {
                copy($source, $temporaryTargetPathAndFilename);
            } catch (\Exception $exception) {
                throw new StorageException(sprintf('Could not copy the file from "%s" to temporary file "%s".', $source, $temporaryTargetPathAndFilename), 1375198876);
            }
        }

        return $this->importTemporaryFile($temporaryTargetPathAndFilename, $collectionName);
    }

    /**
     * Imports a resource from the given string content into this storage.
     *
     * On a successful import this method returns a PersistentResource object representing the newly
     * imported persistent resource.
     *
     * The specified filename will be used when presenting the resource to a user. Its file extension is
     * important because the resource management will derive the IANA Media Type from it.
     *
     * @param string $content The actual content to import
     * @param string $collectionName Name of the collection the new PersistentResource belongs to
     * @return PersistentResource A resource object representing the imported resource
     * @throws StorageException
     */
    public function importResourceFromContent($content, $collectionName)
    {
        $temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('Neos_Flow_ResourceImport_');
        try {
            file_put_contents($temporaryTargetPathAndFilename, $content);
        } catch (\Exception $exception) {
            throw new StorageException(sprintf('Could import the content stream to temporary file "%s".', $temporaryTargetPathAndFilename), 1381156098);
        }

        return $this->importTemporaryFile($temporaryTargetPathAndFilename, $collectionName);
    }

    /**
     * Deletes the storage data related to the given PersistentResource object
     *
     * @param PersistentResource $resource The PersistentResource to delete the storage data of
     * @return boolean TRUE if removal was successful
     */
    public function deleteResource(PersistentResource $resource)
    {
        $pathAndFilename = $this->getStoragePathAndFilenameByHash($resource->getSha1());
        if (!file_exists($pathAndFilename)) {
            return true;
        }
        if (unlink($pathAndFilename) === false) {
            return false;
        }
        Files::removeEmptyDirectoriesOnPath(dirname($pathAndFilename));
        return true;
    }

    /**
     * Imports the given temporary file into the storage and creates the new resource object.
     *
     * Note: the temporary file is (re-)moved by this method.
     *
     * @param string $temporaryPathAndFileName
     * @param string $collectionName
     * @return PersistentResource
     * @throws StorageException
     */
    protected function importTemporaryFile($temporaryPathAndFileName, $collectionName)
    {
        $this->fixFilePermissions($temporaryPathAndFileName);
        $sha1Hash = sha1_file($temporaryPathAndFileName);
        $targetPathAndFilename = $this->getStoragePathAndFilenameByHash($sha1Hash);

        if (!is_file($targetPathAndFilename)) {
            $this->moveTemporaryFileToFinalDestination($temporaryPathAndFileName, $targetPathAndFilename);
        } else {
            unlink($temporaryPathAndFileName);
        }

        $resource = new PersistentResource();
        $resource->setFileSize(filesize($targetPathAndFilename));
        $resource->setCollectionName($collectionName);
        $resource->setSha1($sha1Hash);
        $resource->setMd5(md5_file($targetPathAndFilename));

        return $resource;
    }

    /**
     * Move a temporary file to the final destination, creating missing path segments on the way.
     *
     * @param string $temporaryFile
     * @param string $finalTargetPathAndFilename
     * @return void
     * @throws StorageException
     */
    protected function moveTemporaryFileToFinalDestination($temporaryFile, $finalTargetPathAndFilename)
    {
        if (!file_exists(dirname($finalTargetPathAndFilename))) {
            Files::createDirectoryRecursively(dirname($finalTargetPathAndFilename));
        }
        if (copy($temporaryFile, $finalTargetPathAndFilename) === false) {
            throw new StorageException(sprintf('The temporary file of the file import could not be moved to the final target "%s".', $finalTargetPathAndFilename), 1381156103);
        }
        unlink($temporaryFile);

        $this->fixFilePermissions($finalTargetPathAndFilename);
    }

    /**
     * Fixes the permissions as needed for Flow to run fine in web and cli context.
     *
     * @param string $pathAndFilename
     * @return void
     */
    protected function fixFilePermissions($pathAndFilename)
    {
        @chmod($pathAndFilename, 0666 ^ umask());
    }
}

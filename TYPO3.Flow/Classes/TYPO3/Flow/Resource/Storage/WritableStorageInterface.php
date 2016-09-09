<?php
namespace TYPO3\Flow\Resource\Storage;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Resource\Resource as PersistentResource;
use TYPO3\Flow\Resource\Storage\Exception as StorageException;

/**
 * Interface of a Resource Storage which provides import functionality.
 *
 * @api
 */
interface WritableStorageInterface extends StorageInterface
{
    /**
     * Imports a resource (file) from the given URI or PHP resource stream into this storage.
     *
     * On a successful import this method returns a Resource object representing the newly
     * imported persistent resource.
     *
     * @param string | resource $source The URI (or local path and filename) or the PHP resource stream to import the resource from
     * @param string $collectionName Name of the collection the new Resource belongs to
     * @return PersistentResource A resource object representing the imported resource
     * @throws StorageException
     * @api
     */
    public function importResource($source, $collectionName);

    /**
     * Imports a resource from the given string content into this storage.
     *
     * On a successful import this method returns a Resource object representing the newly
     * imported persistent resource.
     *
     * The specified filename will be used when presenting the resource to a user. Its file extension is
     * important because the resource management will derive the IANA Media Type from it.
     *
     * @param string $content The actual content to import
     * @param string $collectionName Name of the collection the new Resource belongs to
     * @return PersistentResource A resource object representing the imported resource
     * @throws StorageException
     * @api
     */
    public function importResourceFromContent($content, $collectionName);

    /**
     * Deletes the storage data related to the given Resource object
     *
     * Note: Implementations of this method are triggered by a pre-remove event of the persistence layer whenever a
     *       Resource object is going to be removed. Therefore this method must not remove the Resource object from
     *       the Resource Repository itself!
     *
     * @param PersistentResource $resource The Resource to delete the storage data of
     * @return boolean TRUE if removal was successful
     */
    public function deleteResource(PersistentResource $resource);
}

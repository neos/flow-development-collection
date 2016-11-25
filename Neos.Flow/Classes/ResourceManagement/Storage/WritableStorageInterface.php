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

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Storage\Exception as StorageException;

/**
 * Interface of a PersistentResource Storage which provides import functionality.
 *
 * @api
 */
interface WritableStorageInterface extends StorageInterface
{
    /**
     * Imports a resource (file) from the given URI or PHP resource stream into this storage.
     *
     * On a successful import this method returns a PersistentResource object representing the newly
     * imported persistent resource.
     *
     * @param string | resource $source The URI (or local path and filename) or the PHP resource stream to import the resource from
     * @param string $collectionName Name of the collection the new PersistentResource belongs to
     * @return PersistentResource A resource object representing the imported resource
     * @throws StorageException
     * @api
     */
    public function importResource($source, $collectionName);

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
     * @api
     */
    public function importResourceFromContent($content, $collectionName);

    /**
     * Deletes the storage data related to the given PersistentResource object
     *
     * Note: Implementations of this method are triggered by a pre-remove event of the persistence layer whenever a
     *       PersistentResource object is going to be removed. Therefore this method must not remove the PersistentResource object from
     *       the PersistentResource Repository itself!
     *
     * @param PersistentResource $resource The PersistentResource to delete the storage data of
     * @return boolean TRUE if removal was successful
     */
    public function deleteResource(PersistentResource $resource);
}

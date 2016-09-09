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

use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Resource as PersistentResource;
use TYPO3\Flow\Resource\Storage\Object as StorageObject;

/**
 * Interface for a resource storage
 *
 * @api
 */
interface StorageInterface
{
    /**
     * Returns the instance name of this storage
     *
     * @return string
     * @api
     */
    public function getName();

    /**
     * Returns a stream handle which can be used internally to open / copy the given resource
     * stored in this storage.
     *
     * @param PersistentResource $resource The resource stored in this storage
     * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
     * @api
     */
    public function getStreamByResource(PersistentResource $resource);

    /**
     * Returns a stream handle which can be used internally to open / copy the given resource
     * stored in this storage.
     *
     * @param string $relativePath A path relative to the storage root, for example "MyFirstDirectory/SecondDirectory/Foo.css"
     * @return resource | boolean A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
     * @api
     */
    public function getStreamByResourcePath($relativePath);

    /**
     * Retrieve all Objects stored in this storage.
     *
     * @return array<StorageObject>
     * @api
     */
    public function getObjects();

    /**
     * Retrieve all Objects stored in this storage, filtered by the given collection name
     *
     * @param CollectionInterface $collection
     * @return array<StorageObject>
     * @api
     */
    public function getObjectsByCollection(CollectionInterface $collection);
}

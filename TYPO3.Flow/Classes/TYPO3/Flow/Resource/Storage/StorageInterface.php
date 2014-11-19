<?php
namespace TYPO3\Flow\Resource\Storage;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Resource;

/**
 * Interface for a resource storage
 *
 * @api
 */
interface StorageInterface {

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
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource stored in this storage
	 * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
	 * @api
	 */
	public function getStreamByResource(Resource $resource);

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
	 * @return array<\TYPO3\Flow\Resource\Storage\Object>
	 * @api
	 */
	public function getObjects();

	/**
	 * Retrieve all Objects stored in this storage, filtered by the given collection name
	 *
	 * @param CollectionInterface $collection
	 * @return array<\TYPO3\Flow\Resource\Storage\Object>
	 * @api
	 */
	public function getObjectsByCollection(CollectionInterface $collection);

}

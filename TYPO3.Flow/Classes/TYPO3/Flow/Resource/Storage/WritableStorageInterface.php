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

use TYPO3\Flow\Resource\Resource;

/**
 * Interface of a Resource Storage which provides import functionality.
 *
 * @api
 */
interface WritableStorageInterface extends StorageInterface {

	/**
	 * Imports a resource (file) from the given URI or PHP resource stream into this storage.
	 *
	 * On a successful import this method returns a Resource object representing the newly
	 * imported persistent resource.
	 *
	 * @param string | resource $source The URI (or local path and filename) or the PHP resource stream to import the resource from
	 * @param string $collectionName Name of the collection the new Resource belongs to
	 * @return Resource A resource object representing the imported resource
	 * @throws Exception
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
	 * @param string $filename The filename to use for the newly generated resource
	 * @return Resource A resource object representing the imported resource
	 * @throws Exception
	 * @api
	 */
	public function importResourceFromContent($content, $collectionName, $filename);

	/**
	 * Imports a resource (file) as specified in the given upload info array as a
	 * persistent resource.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @param string $collectionName Name of the collection this uploaded resource should be part of
	 * @return Resource A resource object representing the imported resource
	 * @throws Exception
	 * @api
	 */
	public function importUploadedResource(array $uploadInfo, $collectionName);

	/**
	 * Deletes the storage data related to the given Resource object
	 *
	 * Note: Implementations of this method are triggered by a pre-remove event of the persistence layer whenever a
	 *       Resource object is going to be removed. Therefore this method must not remove the Resource object from
	 *       the Resource Repository itself!
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The Resource to delete the storage data of
	 * @return boolean TRUE if removal was successful
	 */
	public function deleteResource(Resource $resource);

}

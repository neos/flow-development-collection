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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Files;

/**
 * A resource storage based on the (local) file system
 */
class WritableFileSystemStorage extends FileSystemStorage implements WritableStorageInterface {

	/**
	 * Initializes this resource storage
	 *
	 * @return void
	 * @throws Exception
	 */
	public function initializeObject() {
		if (!is_writable($this->path)) {
			Files::createDirectoryRecursively($this->path);
		}
		if (!is_dir($this->path) && !is_link($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a resource storage does not exist.', 1361533189);
		}
		if (!is_writable($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a resource storage is not writable.', 1361533190);
		}
	}

	/**
	 * Imports a resource (file) from the given URI or PHP resource stream into this storage.
	 *
	 * On a successful import this method returns a Resource object representing the newly imported persistent resource.
	 *
	 * @param string | resource $source The URI (or local path and filename) or the PHP resource stream to import the resource from
	 * @param string $collectionName Name of the collection the new Resource belongs to
	 * @throws Exception
	 * @return Resource A resource object representing the imported resource
	 */
	public function importResource($source, $collectionName) {
		$temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('TYPO3_Flow_ResourceImport_');

		if (is_resource($source)) {
			try {
				$target = fopen($temporaryTargetPathAndFilename, 'wb');
				stream_copy_to_stream($source, $target);
				fclose($target);
			} catch (\Exception $e) {
				throw new Exception(sprintf('Could import the content stream to temporary file "%s".', $temporaryTargetPathAndFilename), 1380880079);
			}
		} else {
			try {
				copy($source, $temporaryTargetPathAndFilename);
			} catch (\Exception $e) {
				throw new Exception(sprintf('Could not copy the file from "%s" to temporary file "%s".', $source, $temporaryTargetPathAndFilename), 1375198876);
			}
		}

		return $this->importTemporaryFile($temporaryTargetPathAndFilename, $collectionName);
	}

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
	 * @return Resource A resource object representing the imported resource
	 * @throws Exception
	 */
	public function importResourceFromContent($content, $collectionName) {
		$temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('TYPO3_Flow_ResourceImport_');
		try {
			file_put_contents($temporaryTargetPathAndFilename, $content);
		} catch (\Exception $e) {
			throw new Exception(sprintf('Could import the content stream to temporary file "%s".', $temporaryTargetPathAndFilename), 1381156098);
		}

		return $this->importTemporaryFile($temporaryTargetPathAndFilename, $collectionName);
	}

	/**
	 * Deletes the storage data related to the given Resource object
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The Resource to delete the storage data of
	 * @return boolean TRUE if removal was successful
	 */
	public function deleteResource(Resource $resource) {
		$pathAndFilename = $this->getStoragePathAndFilenameByHash($resource->getSha1());
		if (!file_exists($pathAndFilename)) {
			return TRUE;
		}
		if (unlink($pathAndFilename) === FALSE) {
			return FALSE;
		}
		Files::removeEmptyDirectoriesOnPath(dirname($pathAndFilename));
		return TRUE;
	}

	/**
	 * Imports the given temporary file into the storage and creates the new resource object.
	 *
	 * @param string $temporaryFile
	 * @param string $collectionName
	 * @return Resource
	 * @throws Exception
	 */
	protected function importTemporaryFile($temporaryFile, $collectionName) {
		$this->fixFilePermissions($temporaryFile);
		$sha1Hash = sha1_file($temporaryFile);
		$finalTargetPathAndFilename = $this->getStoragePathAndFilenameByHash($sha1Hash);
		if (!file_exists(dirname($finalTargetPathAndFilename))) {
			Files::createDirectoryRecursively(dirname($finalTargetPathAndFilename));
		}
		if (rename($temporaryFile, $finalTargetPathAndFilename) === FALSE) {
			unlink($temporaryFile);
			throw new Exception(sprintf('The temporary file of the file import could not be moved to the final target "%s".', $finalTargetPathAndFilename), 1381156103);
		}

		$this->fixFilePermissions($finalTargetPathAndFilename);

		$resource = new Resource();
		$resource->setFileSize(filesize($finalTargetPathAndFilename));
		$resource->setCollectionName($collectionName);
		$resource->setSha1($sha1Hash);
		$resource->setMd5(md5_file($finalTargetPathAndFilename));

		return $resource;
	}

	/**
	 * Fixes the permissions as needed for Flow to run fine in web and cli context.
	 *
	 * @param string $pathAndFilename
	 * @return void
	 */
	protected function fixFilePermissions($pathAndFilename) {
		@chmod($pathAndFilename, 0666 ^ umask());
	}

}

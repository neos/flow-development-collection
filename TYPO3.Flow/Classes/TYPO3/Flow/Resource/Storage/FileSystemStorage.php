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
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceManager;

/**
 * A resource storage based on the (local) file system
 */
class FileSystemStorage implements StorageInterface {

	/**
	 * Name which identifies this resource storage
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The path (in a filesystem) where resources are stored
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceRepository
	 */
	protected $resourceRepository;

	/**
	 * Constructor
	 *
	 * @param string $name Name of this storage instance, according to the resource settings
	 * @param array $options Options for this storage
	 * @throws Exception
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
		foreach ($options as $key => $value) {
			switch ($key) {
				case 'path':
					$this->$key = $value;
				break;
				default:
					if ($value !== NULL) {
						throw new Exception(sprintf('An unknown option "%s" was specified in the configuration of a resource FileSystemStorage. Please check your settings.', $key), 1361533187);
					}
			}
		}
	}

	/**
	 * Initializes this resource storage
	 *
	 * @return void
	 * @throws Exception If the storage directory does not exist
	 */
	public function initializeObject() {
		if (!is_dir($this->path) && !is_link($this->path)) {
			throw new Exception('The directory "' . $this->path . '" which was configured as a resource storage does not exist.', 1361533189);
		}
	}

	/**
	 * Returns the instance name of this storage
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns a stream handle which can be used internally to open / copy the given resource
	 * stored in this storage.
	 *
	 * @param Resource $resource The resource stored in this storage
	 * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
	 */
	public function getStreamByResource(Resource $resource) {
		$pathAndFilename = $this->getStoragePathAndFilenameByHash($resource->getSha1());
		return (file_exists($pathAndFilename) ? fopen($pathAndFilename, 'rb') : FALSE);
	}

	/**
	 * Returns a stream handle which can be used internally to open / copy the given resource
	 * stored in this storage.
	 *
	 * @param string $relativePath A path relative to the storage root, for example "MyFirstDirectory/SecondDirectory/Foo.css"
	 * @return resource | boolean A URI (for example the full path and filename) leading to the resource file or FALSE if it does not exist
	 */
	public function getStreamByResourcePath($relativePath) {
		$pathAndFilename = $this->path . ltrim($relativePath, '/');
		return (file_exists($pathAndFilename) ? fopen($pathAndFilename, 'r') : FALSE);
	}

	/**
	 * Retrieve all Objects stored in this storage.
	 *
	 * @return array<\TYPO3\Flow\Resource\Storage\Object>
	 */
	public function getObjects() {
		$objects = array();
		foreach ($this->resourceManager->getCollectionsByStorage($this) as $collection) {
			$objects = array_merge($objects, $this->getObjectsByCollection($collection));
		}
		return $objects;
	}

	/**
	 * Retrieve all Objects stored in this storage, filtered by the given collection name
	 *
	 * @param CollectionInterface $collection
	 * @return array<\TYPO3\Flow\Resource\Storage\Object>
	 */
	public function getObjectsByCollection(CollectionInterface $collection) {
		$objects = array();
		$that = $this;
		foreach ($this->resourceRepository->findByCollectionName($collection->getName()) as $resource) {
			/** @var \TYPO3\Flow\Resource\Resource $resource */
			$object = new Object();
			$object->setFilename($resource->getFilename());
			$object->setSha1($resource->getSha1());
			$object->setMd5($resource->getMd5());
			$object->setFileSize($resource->getFileSize());
			$object->setStream(function () use ($that, $resource) { return $that->getStreamByResource($resource); } );
			$objects[] = $object;
		}
		return $objects;
	}

	/**
	 * Determines and returns the absolute path and filename for a storage file identified by the given SHA1 hash.
	 *
	 * This function assures a nested directory structure in order to avoid thousands of files in a single directory
	 * which may result in performance problems in older file systems such as ext2, ext3 or NTFS.
	 *
	 * @param string $sha1Hash The SHA1 hash identifying the stored resource
	 * @return string The path and filename, for example "/var/www/mysite.com/Data/Persistent/c828d/0f88c/e197b/e1aff/7cc2e/5e86b/12442/41ac6/c828d0f88ce197be1aff7cc2e5e86b1244241ac6"
	 */
	protected function getStoragePathAndFilenameByHash($sha1Hash) {
		return $this->path . wordwrap($sha1Hash, 5, '/', TRUE) . '/' . $sha1Hash;
	}

}

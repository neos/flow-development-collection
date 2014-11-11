<?php
namespace TYPO3\Flow\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Resource\Storage\StorageInterface;
use TYPO3\Flow\Resource\Storage\WritableStorageInterface;
use TYPO3\Flow\Resource\Streams\StreamWrapperAdapter;
use TYPO3\Flow\Resource\Target\TargetInterface;

/**
 * The Resource Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ResourceManager {

	/**
	 * Names of the default collections for static and persistent resources.
	 */
	const DEFAULT_STATIC_COLLECTION_NAME = 'static';
	const DEFAULT_PERSISTENT_COLLECTION_NAME = 'persistent';

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var ResourceRepository
	 */
	protected $resourceRepository;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \SplObjectStorage
	 */
	protected $importedResources;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array<\TYPO3\Flow\Resource\Storage\StorageInterface>
	 */
	protected $storages;

	/**
	 * @var array<\TYPO3\Flow\Resource\Target\TargetInterface>
	 */
	protected $targets;

	/**
	 * @var array<\TYPO3\Flow\Resource\CollectionInterface>
	 */
	protected $collections;

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes the Resource Manager by parsing the related configuration and registering the resource
	 * stream wrapper.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->initializeStreamWrapper();
		$this->initializeStorages();
		$this->initializeTargets();
		$this->initializeCollections();

		$this->importedResources = new \SplObjectStorage();
	}

	/**
	 * Imports a resource (file) from the given location as a persistent resource.
	 *
	 * On a successful import this method returns a Resource object representing the
	 * newly imported persistent resource and automatically publishes it to the configured
	 * publication target.
	 *
	 * @param string | resource $source A URI (can therefore also be a path and filename) or a PHP resource stream(!) pointing to the Resource to import
	 * @param string $collectionName Name of the collection this new resource should be added to. By default the standard collection for persistent resources is used.
	 * @param string $forcedPersistenceObjectIdentifier Force the object identifier for this resource to the given UUID
	 * @return \TYPO3\Flow\Resource\Resource A resource object representing the imported resource
	 * @throws Exception
	 * @api
	 */
	public function importResource($source, $collectionName = ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME, $forcedPersistenceObjectIdentifier = NULL) {
		if (!isset($this->collections[$collectionName])) {
			throw new Exception(sprintf('Tried to import a file into the resource collection "%s" but no such collection exists. Please check your settings and the code which triggered the import.', $collectionName), 1375196643);
		}

		/* @var CollectionInterface $collection */
		$collection = $this->collections[$collectionName];

		try {
			$resource = $collection->importResource($source);
			if ($forcedPersistenceObjectIdentifier !== NULL) {
				ObjectAccess::setProperty($resource, 'Persistence_Object_Identifier', $forcedPersistenceObjectIdentifier, TRUE);
			}
		} catch (Exception $e) {
			throw new Exception(sprintf('Importing a file into the resource collection "%s" failed: %s', $collectionName, $e->getMessage()), 1375197120, $e);
		}
		/* @var Resource $resource */

		$this->resourceRepository->add($resource);
		$this->systemLogger->log(sprintf('Successfully imported file "%s" into the resource collection "%s" (storage: %s, a %s. SHA1: %s)', $source, $collectionName, $collection->getStorage()->getName(), get_class($collection), $resource->getSha1()), LOG_DEBUG);
		return $resource;
	}

	/**
	 * Imports the given content passed as a string as a new persistent resource.
	 *
	 * The given content typically is binary data or a text format. On a successful import this method
	 * returns a Resource object representing the imported content and automatically publishes it to the
	 * configured publication target.
	 *
	 * The specified filename will be used when presenting the resource to a user. Its file extension is
	 * important because the resource management will derive the IANA Media Type from it.
	 *
	 * @param string $content The binary content to import
	 * @param string $filename The filename to use for the newly generated resource
	 * @param string $collectionName Name of the collection this new resource should be added to. By default the standard collection for persistent resources is used.
	 * @return Resource A resource object representing the imported resource
	 * @throws Exception
	 */
	public function importResourceFromContent($content, $filename, $collectionName = ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME, $forcedPersistenceObjectIdentifier = NULL) {
		if (!is_string($content)) {
			throw new Exception(sprintf('Tried to import content into the resource collection "%s" but the given content was a %s instead of a string.', $collectionName, gettype($content)), 1380878115);
		}
		if (!isset($this->collections[$collectionName])) {
			throw new Exception(sprintf('Tried to import a file into the resource collection "%s" but no such collection exists. Please check your settings and the code which triggered the import.', $collectionName), 1380878131);
		}

		/* @var CollectionInterface $collection */
		$collection = $this->collections[$collectionName];

		try {
			$resource = $collection->importResourceFromContent($content, $filename);
			if ($forcedPersistenceObjectIdentifier !== NULL) {
				ObjectAccess::setProperty($resource, 'Persistence_Object_Identifier', $forcedPersistenceObjectIdentifier, TRUE);
			}
		} catch (Exception $e) {
			throw new Exception(sprintf('Importing content into the resource collection "%s" failed: %s', $collectionName, $e->getMessage()), 1381156155, $e);
		}

		$this->resourceRepository->add($resource);
		$this->systemLogger->log(sprintf('Successfully imported content into the resource collection "%s" (storage: %s, a %s. SHA1: %s)', $collectionName, $collection->getStorage()->getName(), get_class($collection->getStorage()), $resource->getSha1()), LOG_DEBUG);

		return $resource;
	}

	/**
	 * Imports a resource (file) from the given upload info array as a persistent
	 * resource.
	 *
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @param string $collectionName Name of the collection this uploaded resource should be added to
	 * @return Resource A resource object representing the imported resource or FALSE if an error occurred.
	 * @throws Exception
	 */
	public function importUploadedResource(array $uploadInfo, $collectionName = self::DEFAULT_PERSISTENT_COLLECTION_NAME) {
		if (!isset($this->collections[$collectionName])) {
			throw new Exception(sprintf('Tried to import an uploaded file into the resource collection "%s" but no such collection exists. Please check your settings and HTML forms.', $collectionName), 1375197544);
		}

		/* @var CollectionInterface $collection */
		$collection = $this->collections[$collectionName];

		try {
			/** @var Resource $resource */
			$resource = $collection->importUploadedResource($uploadInfo);
		} catch (Exception $e) {
			throw new Exception(sprintf('Importing an uploaded file into the resource collection "%s" failed.', $collectionName), 1375197680, $e);
		}

		$this->resourceRepository->add($resource);
		$this->systemLogger->log(sprintf('Successfully imported the uploaded file "%s" into the resource collection "%s" (storage: "%s", a %s. SHA1: %s)', $resource->getFilename(), $collectionName, $this->collections[$collectionName]->getStorage()->getName(), get_class($this->collections[$collectionName]->getStorage()), $resource->getSha1()), LOG_DEBUG);

		return $resource;
	}

	/**
	 * Returns the resource object identified by the given SHA1 hash over the content, or NULL if no such Resource
	 * object is known yet.
	 *
	 * @param string $sha1Hash The SHA1 identifying the data the Resource stands for
	 * @return Resource | NULL
	 * @api
	 */
	public function getResourceBySha1($sha1Hash) {
		return $this->resourceRepository->findOneBySha1($sha1Hash);
	}

	/**
	 * Returns a stream handle of the given persistent resource which allows for opening / copying the resource's
	 * data. Note that this stream handle may only be used read-only.
	 *
	 * @param Resource $resource The resource to retrieve the stream for
	 * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
	 * @api
	 */
	public function getStreamByResource(Resource $resource) {
		$collectionName = $resource->getCollectionName();
		if (!isset($this->collections[$collectionName])) {
			return FALSE;
		}
		return $this->collections[$collectionName]->getStreamByResource($resource);
	}

	/**
	 * Returns an object storage with all resource objects which have been imported
	 * by the Resource Manager during this script call. Each resource comes with
	 * an array of additional information about its import.
	 *
	 * Example for a returned object storage:
	 *
	 * $resource1 => array('originalFilename' => 'Foo.txt'),
	 * $resource2 => array('originalFilename' => 'Bar.txt'),
	 * ...
	 *
	 * @return \SplObjectStorage
	 * @api
	 */
	public function getImportedResources() {
		return $this->resourceRepository->getAddedResources();
	}

	/**
	 * Deletes the given Resource from the Resource Repository and, if the storage data is no longer used in another
	 * Resource object, also deletes the data from the storage.
	 *
	 * This method will also remove the Resource object from the (internal) ResourceRepository.
	 *
	 * @param Resource $resource The resource to delete
	 * @param boolean $unpublishResource If the resource should be unpublished before deleting it from the storage
	 * @return boolean TRUE if the resource was deleted, otherwise FALSE
	 * @api
	 */
	public function deleteResource(Resource $resource, $unpublishResource = TRUE) {
		$collectionName = $resource->getCollectionName();

		if ($unpublishResource) {
			/** @var TargetInterface $target */
			$target = $this->collections[$collectionName]->getTarget();
			$target->unpublishResource($resource);
		}

		$result = $this->resourceRepository->findBySha1($resource->getSha1());
		if (count($result) > 1) {
			$this->systemLogger->log(sprintf('Not removing storage data of resource %s (%s) because it is still in use by %s other Resource object(s).', $resource->getFilename(), $resource->getSha1(), count($result) - 1), LOG_DEBUG);
		} else {
			if (!isset($this->collections[$collectionName])) {
				$this->systemLogger->log(sprintf('Could not remove storage data of resource %s (%s) because it refers to the unknown collection "%s".', $resource->getFilename(), $resource->getSha1(), $collectionName), LOG_WARNING);
				return FALSE;
			}
			$storage = $this->collections[$collectionName]->getStorage();
			if (!$storage instanceof WritableStorageInterface) {
				$this->systemLogger->log(sprintf('Could not remove storage data of resource %s (%s) because it its collection "%s" is read-only.', $resource->getFilename(), $resource->getSha1(), $collectionName), LOG_WARNING);
				return FALSE;
			}
			try {
				$storage->deleteResource($resource);
			} catch (\Exception $e) {
				$this->systemLogger->log(sprintf('Could not remove storage data of resource %s (%s): %s.', $resource->getFilename(), $resource->getSha1(), $e->getMessage()), LOG_WARNING);
				return FALSE;
			}
			if ($unpublishResource) {
				$this->systemLogger->log(sprintf('Removed storage data and unpublished resource %s (%s) because it not used by any other Resource object.', $resource->getFilename(), $resource->getSha1()), LOG_DEBUG);
			} else {
				$this->systemLogger->log(sprintf('Removed storage data of resource %s (%s) because it not used by any other Resource object.', $resource->getFilename(), $resource->getSha1()), LOG_DEBUG);
			}
		}

		$this->resourceRepository->remove($resource);
		return TRUE;
	}

	/**
	 * Returns the web accessible URI for the given resource object
	 *
	 * @param Resource $resource The resource object
	 * @return string | boolean A URI as a string or FALSE if the collection of the resource is not found
	 * @api
	 */
	public function getPublicPersistentResourceUri(Resource $resource) {
		if (!isset($this->collections[$resource->getCollectionName()])) {
			return FALSE;
		}
		/** @var TargetInterface $target */
		$target = $this->collections[$resource->getCollectionName()]->getTarget();
		return $target->getPublicPersistentResourceUri($resource);
	}

	/**
	 * Returns the web accessible URI for the resource object specified by the
	 * given SHA1 hash.
	 *
	 * @param string $resourceHash The SHA1 hash identifying the resource content
	 * @param string $collectionName Name of the collection the resource is part of
	 * @return string A URI as a string
	 * @throws Exception
	 * @api
	 */
	public function getPublicPersistentResourceUriByHash($resourceHash, $collectionName = self::DEFAULT_PERSISTENT_COLLECTION_NAME) {
		if (!isset($this->collections[$collectionName])) {
			throw new Exception(sprintf('Could not determine persistent resource URI for "%s" because the specified collection "%s" does not exist.', $resourceHash, $collectionName), 1375197875);
		}
		/** @var TargetInterface $target */
		$target = $this->collections[$collectionName]->getTarget();
		$resource = $this->resourceRepository->findOneBySha1($resourceHash);
		if ($resource === NULL) {
			throw new Exception(sprintf('Could not determine persistent resource URI for "%s" because no Resource object with that SHA1 hash could be found.', $resourceHash), 1375347691);
		}
		return $target->getPublicPersistentResourceUri($resourceHash);
	}

	/**
	 * Returns the public URI for a static resource provided by the specified package and in the given
	 * path below the package's resources directory.
	 *
	 * @param string $packageKey Package key
	 * @param string $relativePathAndFilename A relative path below the "Resources" directory of the package
	 * @return string
	 */
	public function getPublicPackageResourceUri($packageKey, $relativePathAndFilename) {
		/** @var TargetInterface $target */
		$target = $this->collections[self::DEFAULT_STATIC_COLLECTION_NAME]->getTarget();
		return $target->getPublicStaticResourceUri($packageKey . '/' . $relativePathAndFilename);
	}

	/**
	 * Returns a Storage instance by the given name
	 *
	 * @param string $storageName Name of the storage as defined in the settings
	 * @return \TYPO3\Flow\Resource\Storage\StorageInterface or NULL
	 */
	public function getStorage($storageName) {
		return isset($this->storages[$storageName]) ? $this->storages[$storageName] : NULL;
	}

	/**
	 * Returns a Collection instance by the given name
	 *
	 * @param string $collectionName Name of the collection as defined in the settings
	 * @return \TYPO3\Flow\Resource\CollectionInterface or NULL
	 */
	public function getCollection($collectionName) {
		return isset($this->collections[$collectionName]) ? $this->collections[$collectionName] : NULL;
	}

	/**
	 * Returns an array of currently known Collection instances
	 *
	 * @return array<\TYPO3\Flow\Resource\CollectionInterface>
	 */
	public function getCollections() {
		return $this->collections;
	}

	/**
	 * Returns an array of Collection instances which use the given storage
	 *
	 * @param StorageInterface $storage
	 * @return array<\TYPO3\Flow\Resource\CollectionInterface>
	 */
	public function getCollectionsByStorage(StorageInterface $storage) {
		$collections = array();
		foreach ($this->collections as $collectionName => $collection) {
			/** @var CollectionInterface $collection */
			if ($collection->getStorage() === $storage) {
				$collections[$collectionName] = $collection;
			}
		}
		return $collections;
	}

	/**
	 * Checks if recently imported resources really have been persisted - and if not, removes its data from the
	 * respective storage.
	 *
	 * @return void
	 */
	public function shutdownObject() {
		foreach ($this->resourceRepository->getAddedResources() as $resource) {
			if ($this->persistenceManager->isNewObject($resource)) {
				$this->deleteResource($resource, FALSE);
			}
		}
	}

	/**
	 * Initializes the Storage objects according to the current settings
	 *
	 * @return void
	 * @throws Exception if the storage configuration is invalid
	 */
	protected function initializeStorages() {
		foreach ($this->settings['resource']['storages'] as $storageName => $storageDefinition) {
			if (!isset($storageDefinition['storage'])) {
				throw new Exception(sprintf('The configuration for the resource storage "%s" defined in your settings has no valid "storage" option. Please check the configuration syntax and make sure to specify a valid storage class name.', $storageName), 1361467211);
			}
			if (!class_exists($storageDefinition['storage'])) {
				throw new Exception(sprintf('The configuration for the resource storage "%s" defined in your settings has not defined a valid "storage" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $storageName, $storageDefinition['storage']), 1361467212);
			}
			$options = (isset($storageDefinition['storageOptions']) ? $storageDefinition['storageOptions'] : array());
			$this->storages[$storageName] = new $storageDefinition['storage']($storageName, $options);
		}
	}

	/**
	 * Initializes the Target objects according to the current settings
	 *
	 * @return void
	 * @throws Exception if the target configuration is invalid
	 */
	protected function initializeTargets() {
		foreach ($this->settings['resource']['targets'] as $targetName => $targetDefinition) {
			if (!isset($targetDefinition['target'])) {
				throw new Exception(sprintf('The configuration for the resource target "%s" defined in your settings has no valid "target" option. Please check the configuration syntax and make sure to specify a valid target class name.', $targetName), 1361467838);
			}
			if (!class_exists($targetDefinition['target'])) {
				throw new Exception(sprintf('The configuration for the resource target "%s" defined in your settings has not defined a valid "target" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $targetName, $targetDefinition['target']), 1361467839);
			}
			$options = (isset($targetDefinition['targetOptions']) ? $targetDefinition['targetOptions'] : array());
			$this->targets[$targetName] = new $targetDefinition['target']($targetName, $options);
		}
	}

	/**
	 * Initializes the Collection objects according to the current settings
	 *
	 * @return void
	 * @throws Exception if the collection configuration is invalid
	 */
	protected function initializeCollections() {
		foreach ($this->settings['resource']['collections'] as $collectionName => $collectionDefinition) {
			if (!isset($collectionDefinition['storage'])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has no valid "storage" option. Please check the configuration syntax.', $collectionName), 1361468805);
			}
			if (!isset($this->storages[$collectionDefinition['storage']])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings referred to a non-existing storage "%s". Please check the configuration syntax and make sure to specify a valid storage class name.', $collectionName, $collectionDefinition['storage']), 1361481031);
			}
			if (!isset($collectionDefinition['target'])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has no valid "target" option. Please check the configuration syntax and make sure to specify a valid target class name.', $collectionName), 1361468923);
			}
			if (!isset($this->targets[$collectionDefinition['target']])) {
				throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has not defined a valid "target" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $collectionName, $collectionDefinition['target']), 1361468924);
			}

			$pathPatterns = (isset($collectionDefinition['pathPatterns'])) ? $collectionDefinition['pathPatterns'] : array();
			$filenames = (isset($collectionDefinition['filenames'])) ? $collectionDefinition['filenames'] : array();

			$this->collections[$collectionName] = new Collection($collectionName, $this->storages[$collectionDefinition['storage']], $this->targets[$collectionDefinition['target']], $pathPatterns, $filenames);
		}
	}

	/**
	 * Registers a Stream Wrapper Adapter for the resource:// scheme.
	 *
	 * @return void
	 */
	protected function initializeStreamWrapper() {
		$streamWrapperClassNames = static::getStreamWrapperImplementationClassNames($this->objectManager);
		foreach ($streamWrapperClassNames as $streamWrapperClassName) {
			$scheme = $streamWrapperClassName::getScheme();
			if (in_array($scheme, stream_get_wrappers())) {
				stream_wrapper_unregister($scheme);
			}
			stream_wrapper_register($scheme, 'TYPO3\Flow\Resource\Streams\StreamWrapperAdapter');
			StreamWrapperAdapter::registerStreamWrapper($scheme, $streamWrapperClassName);
		}
	}

	/**
	 * Returns all class names implementing the StreamWrapperInterface.
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return array Array of stream wrapper implementations
	 * @Flow\CompileStatic
	 */
	static protected function getStreamWrapperImplementationClassNames($objectManager) {
		return $objectManager->get('TYPO3\Flow\Reflection\ReflectionService')->getAllImplementationClassNamesForInterface('TYPO3\Flow\Resource\Streams\StreamWrapperInterface');
	}

	/**
	 * Creates a resource from the given binary content as a persistent resource.
	 *
	 * @param string $content The binary content to import
	 * @param string $filename The filename to use for the newly generated resource
	 * @return Resource A resource object representing the created resource or FALSE if an error occurred.
	 * @deprecated use importResourceFromContent() instead
	 * @see importResourceFromContent()
	 */
	public function createResourceFromContent($content, $filename) {
		return $this->importResourceFromContent($content, $filename);
	}

}

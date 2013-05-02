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

use TYPO3\Flow\Annotations as Flow;

/**
 * The Resource Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ResourceManager {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $statusCache;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var string
	 */
	protected $persistentResourcesStorageBaseUri;

	/**
	 * @var \SplObjectStorage
	 */
	protected $importedResources;

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
	 * Check for implementations of TYPO3\Flow\Resource\Streams\StreamWrapperInterface and
	 * register them.
	 *
	 * @return void
	 */
	public function initialize() {
		$streamWrapperClassNames = static::getStreamWrapperImplementationClassNames($this->objectManager);
		foreach ($streamWrapperClassNames as $streamWrapperClassName) {
			$scheme = $streamWrapperClassName::getScheme();
			if (in_array($scheme, stream_get_wrappers())) {
				stream_wrapper_unregister($scheme);
			}
			stream_wrapper_register($scheme, '\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter');
			\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter::registerStreamWrapper($scheme, $streamWrapperClassName);
		}

			// For now this URI is hardcoded, but might be manageable in the future
			// if additional persistent resources storages are supported.
		$this->persistentResourcesStorageBaseUri = FLOW_PATH_DATA . 'Persistent/Resources/';
		\TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->persistentResourcesStorageBaseUri);

		$this->importedResources = new \SplObjectStorage();
	}

	/**
	 * Returns all class names implementing the StreamWrapperInterface.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return array Array of stream wrapper implementations
	 * @Flow\CompileStatic
	 */
	static public function getStreamWrapperImplementationClassNames($objectManager) {
		$reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		return $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Resource\Streams\StreamWrapperInterface');
	}

	/**
	 * Imports a resource (file) from the given location as a persistent resource.
	 * On a successful import this method returns a Resource object representing the
	 * newly imported persistent resource.
	 *
	 * @param string $uri An URI (can also be a path and filename) pointing to the resource to import
	 * @return mixed A resource object representing the imported resource or FALSE if an error occurred.
	 * @api
	 */
	public function importResource($uri) {
		$pathInfo = pathinfo($uri);
		if (isset($pathInfo['extension']) && substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
			$this->systemLogger->log('Import of resources with a "php" extension is not allowed.', LOG_WARNING);
			return FALSE;
		}

		$temporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . uniqid('Flow_ResourceImport_');
		if (copy($uri, $temporaryTargetPathAndFilename) === FALSE) {
			$this->systemLogger->log('Could not copy resource from "' . $uri . '" to temporary file "' . $temporaryTargetPathAndFilename . '".', LOG_WARNING);
			return FALSE;
		}

		$hash = sha1_file($temporaryTargetPathAndFilename);
		$finalTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . $hash;
		if (rename($temporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
			unlink($temporaryTargetPathAndFilename);
			$this->systemLogger->log('Could not copy temporary file from "' . $temporaryTargetPathAndFilename . '" to final destination "' . $finalTargetPathAndFilename . '".', LOG_WARNING);
			return FALSE;
		}
		$this->fixFilePermissions($finalTargetPathAndFilename);

		$resource = $this->createResourceFromHashAndFilename($hash, $pathInfo['basename']);
		$this->attachImportedResource($resource);

		return $resource;
	}

	/**
	 * Creates a resource (file) from the given binary content as a persistent resource.
	 * On a successful creation this method returns a Resource object representing the
	 * newly created persistent resource.
	 *
	 * @param mixed $content The binary content of the file
	 * @param string $filename
	 * @return \TYPO3\Flow\Resource\Resource A resource object representing the created resource or FALSE if an error occurred.
	 * @api
	 */
	public function createResourceFromContent($content, $filename) {
		$pathInfo = pathinfo($filename);
		if (isset($pathInfo['extension']) && substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
			$this->systemLogger->log('Creation of resources with a "php" extension is not allowed.', LOG_WARNING);
			return FALSE;
		}

		$hash = sha1($content);
		$finalTargetPathAndFilename = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->persistentResourcesStorageBaseUri, $hash));
		if (!file_exists($finalTargetPathAndFilename)) {
			if (file_put_contents($finalTargetPathAndFilename, $content) === FALSE) {
				$this->systemLogger->log('Could not create resource at "' . $finalTargetPathAndFilename . '".', LOG_WARNING);
				return FALSE;
			} else {
				$this->fixFilePermissions($finalTargetPathAndFilename);
			}
		}

		$resource = $this->createResourceFromHashAndFilename($hash, $pathInfo['basename']);
		$this->attachImportedResource($resource);

		return $resource;
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
		return clone $this->importedResources;
	}

	/**
	 * Imports a resource (file) from the given upload info array as a persistent
	 * resource.
	 * On a successful import this method returns a Resource object representing
	 * the newly imported persistent resource.
	 *
	 * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
	 * @return mixed A resource object representing the imported resource or FALSE if an error occurred.
	 */
	public function importUploadedResource(array $uploadInfo) {
		$pathInfo = pathinfo($uploadInfo['name']);
		if (isset($pathInfo['extension']) && substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
			return FALSE;
		}

		$temporaryTargetPathAndFilename = $uploadInfo['tmp_name'];
		if (!is_uploaded_file($temporaryTargetPathAndFilename)) {
			return FALSE;
		}

		$openBasedirEnabled = (boolean)ini_get('open_basedir');
		if ($openBasedirEnabled === TRUE) {
				// Move uploaded file to a readable folder before trying to read sha1 value of file
			$newTemporaryTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . uniqid();
			if (move_uploaded_file($temporaryTargetPathAndFilename, $newTemporaryTargetPathAndFilename) === FALSE) {
				return FALSE;
			}
			$hash = sha1_file($newTemporaryTargetPathAndFilename);
			$finalTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . $hash;
			if (rename($newTemporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
				return FALSE;
			}
		} else {
			$hash = sha1_file($temporaryTargetPathAndFilename);
			$finalTargetPathAndFilename = $this->persistentResourcesStorageBaseUri . $hash;
			if (move_uploaded_file($temporaryTargetPathAndFilename, $finalTargetPathAndFilename) === FALSE) {
				return FALSE;
			}
		}

		$this->fixFilePermissions($finalTargetPathAndFilename);
		$resource = new \TYPO3\Flow\Resource\Resource();
		$resource->setFilename($pathInfo['basename']);

		$resourcePointer = $this->getResourcePointerForHash($hash);
		$resource->setResourcePointer($resourcePointer);
		$this->importedResources[$resource] = array(
			'originalFilename' => $pathInfo['basename']
		);
		return $resource;
	}

	/**
	 * Helper function which creates or fetches a resource pointer object for a given hash.
	 *
	 * If a ResourcePointer with the given hash exists, this one is used. Else, a new one
	 * is created. This is a workaround for missing ValueObject support in Doctrine.
	 *
	 * @param string $hash
	 * @return \TYPO3\Flow\Resource\ResourcePointer
	 */
	public function getResourcePointerForHash($hash) {
		$resourcePointer = $this->persistenceManager->getObjectByIdentifier($hash, 'TYPO3\Flow\Resource\ResourcePointer');
		if (!$resourcePointer) {
			$resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);
			$this->persistenceManager->add($resourcePointer);
		}

		return $resourcePointer;
	}

	/**
	 * Deletes the file represented by the given resource instance.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return boolean
	 */
	public function deleteResource($resource) {
			// instanceof instead of type hinting so it can be used as slot
		if ($resource instanceof \TYPO3\Flow\Resource\Resource) {
			$this->resourcePublisher->unpublishPersistentResource($resource);
			if (is_file($this->persistentResourcesStorageBaseUri . $resource->getResourcePointer()->getHash())) {
				unlink($this->persistentResourcesStorageBaseUri . $resource->getResourcePointer()->getHash());
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Method which returns the base URI of the location where persistent resources
	 * are stored.
	 *
	 * @return string The URI
	 */
	public function getPersistentResourcesStorageBaseUri() {
		return $this->persistentResourcesStorageBaseUri;
	}

	/**
	 * Prepares a mirror of public package resources that is accessible through
	 * the web server directly.
	 *
	 * @param array $activePackages
	 * @return void
	 */
	public function publishPublicPackageResources(array $activePackages) {
		if ($this->settings['resource']['publishing']['detectPackageResourceChanges'] === FALSE && $this->statusCache->has('packageResourcesPublished')) {
			return;
		}
		foreach ($activePackages as $packageKey => $package) {
			$this->resourcePublisher->publishStaticResources($package->getResourcesPath() . 'Public/', 'Packages/' . $packageKey . '/');
		}
		if (!$this->statusCache->has('packageResourcesPublished')) {
			$this->statusCache->set('packageResourcesPublished', 'y', array(\TYPO3\Flow\Cache\Frontend\FrontendInterface::TAG_PACKAGE));
		}
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

	/**
	 * Creates a resource object from a given hash and filename. The according
	 * resource pointer is fetched automatically.
	 *
	 * @param string $resourceHash
	 * @param string $originalFilename
	 * @return \TYPO3\Flow\Resource\Resource
	 */
	protected function createResourceFromHashAndFilename($resourceHash, $originalFilename) {
		$resource = new \TYPO3\Flow\Resource\Resource();
		$resource->setFilename($originalFilename);

		$resourcePointer = $this->getResourcePointerForHash($resourceHash);
		$resource->setResourcePointer($resourcePointer);

		return $resource;
	}

	/**
	 * Attaches the given resource to the imported resources of this script run
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource
	 * @return void
	 */
	protected function attachImportedResource(\TYPO3\Flow\Resource\Resource $resource) {
		$this->importedResources->attach($resource, array(
			'originalFilename' => $resource->getFilename()
		));
	}
}
?>
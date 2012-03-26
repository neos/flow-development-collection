<?php
namespace TYPO3\FLOW3\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An type converter for ResourcePointer objects
 *
 * @FLOW3\Scope("singleton")
 */
class ResourceTypeConverter extends \TYPO3\FLOW3\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\FLOW3\Resource\Resource';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @var \TYPO3\FLOW3\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var array
	 */
	protected $convertedResources = array();

	/**
	 * Injects the resource manager
	 *
	 * @param \TYPO3\FLOW3\Resource\ResourceManager $resourceManager
	 * @return void
	 */
	public function injectResourceManager(\TYPO3\FLOW3\Resource\ResourceManager $resourceManager) {
		$this->resourceManager = $resourceManager;
	}

	/**
	 * Converts the given string or array to a ResourcePointer object.
	 *
	 * If the input format is an array, this method assumes the resource to be a
	 * fresh file upload and imports the temporary upload file through the
	 * resource manager.
	 *
	 * @param array $source The upload info (expected keys: error, name, tmp_name)
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object An object or an instance of TYPO3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\FLOW3\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (!isset($source['error'])) {
			return new \TYPO3\FLOW3\Error\Error('Could not convert the given input into into a Resource object because the source array is corrupt.' , 1332765679);
		}
		if ($source['error'] === \UPLOAD_ERR_NO_FILE) {
			if (isset($source['submittedFile']) && isset($source['submittedFile']['fileName']) && isset($source['submittedFile']['resourcePointer'])) {
				$resourcePointer = $this->persistenceManager->getObjectByIdentifier($source['submittedFile']['resourcePointer'], 'TYPO3\FLOW3\Resource\ResourcePointer');
				if ($resourcePointer) {
					$resource = new Resource();
					$resource->setFileName($source['submittedFile']['fileName']);
					$resource->setResourcePointer($resourcePointer);
					return $resource;
				}
			}
			return NULL;
		}

		if ($source['error'] !== \UPLOAD_ERR_OK) {
			return new \TYPO3\FLOW3\Error\Error(\TYPO3\FLOW3\Utility\Files::getUploadErrorMessage($source['error']) , 1264440823);
		}

		if (isset($this->convertedResources[$source['tmp_name']])) {
			return $this->convertedResources[$source['tmp_name']];
		}

		$resource = $this->resourceManager->importUploadedResource($source);
		if ($resource === FALSE) {
			return new \TYPO3\FLOW3\Error\Error('The resource manager could not create a Resource instance.' , 1264517906);
		} else {
			$this->convertedResources[$source['tmp_name']] = $resource;
			return $resource;
		}
	}
}

?>
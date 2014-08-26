<?php
namespace TYPO3\Flow\Property\TypeConverter;

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

/**
 * An type converter for converting strings and arrays to Resource objects.
 *
 * If the source
 *
 * - is a string looking like a SHA1 (40 characters [0-9a-f]) or
 * - is an array and contains the key 'hash' with a value looking like a SHA1 (40 characters [0-9a-f])
 *
 * the coverter will look up an existing Resource(Pointer) with that hash and return it if found. If that fails,
 * the converter will try to import a file named like that hash from the configured CONFIGURATION_RESOURCE_LOAD_PATH.
 *
 * If no hash is given in an array source but the key 'data' is set, the content of that key is assumed a binary string
 * and a Resource representing this content is created and returned.
 *
 * The imported Resource will be given a 'filename' if set in the source array in both cases (import from file or data).
 *
 * @Flow\Scope("singleton")
 */
class ResourceConverter extends AbstractTypeConverter {

	/**
	 * @var string
	 */
	const CONFIGURATION_RESOURCE_LOAD_PATH = 'resourceLoadPath';

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\Flow\Resource\Resource';

	/**
	 * @var integer
	 */
	protected $priority = 2;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Converts the given string or array to a Resource object.
	 *
	 * If the input format is an array, this method assumes the resource to be a
	 * fresh file upload and imports the temporary upload file through the
	 * resource manager.
	 *
	 * @param mixed $source The upload info (expected keys: error, name, tmp_name)
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return Resource|TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$hash = NULL;
		$resource = FALSE;
		if (is_string($source) && preg_match('/[0-9a-f]{40}/', $source)) {
			$hash = $source;
		} elseif (is_array($source) && isset($source['hash']) && preg_match('/[0-9a-f]{40}/', $source['hash'])) {
			$hash = $source['hash'];
		}

		if ($hash !== NULL) {
			$resourcePointer = $this->persistenceManager->getObjectByIdentifier($hash, 'TYPO3\Flow\Resource\ResourcePointer');
			if ($resourcePointer) {
				$resource = new Resource();
				$resource->setFilename($source['filename']);
				$resource->setResourcePointer($resourcePointer);

				return $resource;
			}
		}

		if (isset($source['data'])) {
			$resource = $this->resourceManager->createResourceFromContent($source['data'], $source['filename']);
		} elseif ($hash !== NULL) {
			$resource = $this->resourceManager->importResource($configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ResourceConverter', self::CONFIGURATION_RESOURCE_LOAD_PATH) . '/' . $hash);
			if (is_array($source) && isset($source['filename'])) {
				$resource->setFilename($source['filename']);
			}
		}

		if ($resource === FALSE) {
			return new \TYPO3\Flow\Error\Error('The resource manager could not create a Resource instance.', 1404312901);
		} else {
			return $resource;
		}
	}
}

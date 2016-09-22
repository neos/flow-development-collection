<?php
namespace TYPO3\Flow\Resource;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Error as FlowError;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Resource\Resource as PersistentResource;

/**
 * A type converter for converting strings, array and uploaded files to Resource objects.
 *
 * Has two major working modes:
 *
 * 1. File Uploads by PHP
 *
 *    In this case, the input array is expected to be a fresh file upload following the native PHP handling. The
 *    temporary upload file is then imported through the resource manager.
 *
 *    To enable the handling of files that have already been uploaded earlier, the special field ['originallySubmittedResource']
 *    is checked. If set, it is used to fetch a file that has already been uploaded even if no file has been actually uploaded in the current request.
 *
 *
 * 2. Strings / arbitrary Arrays
 *
 *    If the source

 *    - is an array and contains the key '__identity'
 *
 *    the converter will find an existing resource with the given identity or continue and assign the given identity if
 *    CONFIGURATION_IDENTITY_CREATION_ALLOWED is set.
 *
 *    - is a string looking like a SHA1 (40 characters [0-9a-f]) or
 *    - is an array and contains the key 'hash' with a value looking like a SHA1 (40 characters [0-9a-f])
 *
 *    the converter will look up an existing Resource with that hash and return it if found. If that fails,
 *    the converter will try to import a file named like that hash from the configured CONFIGURATION_RESOURCE_LOAD_PATH.
 *
 *    If no hash is given in an array source but the key 'data' is set, the content of that key is assumed a binary string
 *    and a Resource representing this content is created and returned.
 *
 *    The imported Resource will be given a 'filename' if set in the source array in both cases (import from file or data).
 *
 * @Flow\Scope("singleton")
 */
class ResourceTypeConverter extends AbstractTypeConverter
{
    /**
     * @var string
     */
    const CONFIGURATION_RESOURCE_LOAD_PATH = 'resourceLoadPath';

    /**
     * @var integer
     */
    const CONFIGURATION_IDENTITY_CREATION_ALLOWED = 1;

    /**
     * Sets the default resource collection name (see Settings: TYPO3.Flow.resource.collections) to use for this resource,
     * will fallback to ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME
     *
     * @var string
     */
    const CONFIGURATION_COLLECTION_NAME = 'collectionName';

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string', 'array'];

    /**
     * @var string
     */
    protected $targetType = PersistentResource::class;

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

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
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var array
     */
    protected $convertedResources = [];

    /**
     * Converts the given string or array to a PersistentResource object.
     *
     * If the input format is an array, this method assumes the resource to be a
     * fresh file upload and imports the temporary upload file through the
     * ResourceManager.
     *
     * Note that $source['error'] will also be present if a file was successfully
     * uploaded. In that case its value will be \UPLOAD_ERR_OK.
     *
     * @param array $source The upload info (expected keys: error, name, tmp_name)
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return PersistentResource|FlowError if the input format is not supported or could not be converted for other reasons
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (empty($source)) {
            return null;
        }

        if (is_string($source)) {
            $source = ['hash' => $source];
        }

        // $source is ALWAYS an array at this point
        if (isset($source['error']) || isset($source['originallySubmittedResource'])) {
            return $this->handleFileUploads($source, $configuration);
        } elseif (isset($source['hash']) || isset($source['data'])) {
            return $this->handleHashAndData($source, $configuration);
        }
        return null;
    }

    /**
     * @param array $source
     * @param PropertyMappingConfigurationInterface $configuration
     * @return PersistentResource|FlowError
     * @throws \Exception
     */
    protected function handleFileUploads(array $source, PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!isset($source['error']) || $source['error'] === \UPLOAD_ERR_NO_FILE) {
            if (isset($source['originallySubmittedResource']) && isset($source['originallySubmittedResource']['__identity'])) {
                return $this->persistenceManager->getObjectByIdentifier($source['originallySubmittedResource']['__identity'], PersistentResource::class);
            }
            return null;
        }

        if ($source['error'] !== \UPLOAD_ERR_OK) {
            switch ($source['error']) {
                case \UPLOAD_ERR_INI_SIZE:
                case \UPLOAD_ERR_FORM_SIZE:
                case \UPLOAD_ERR_PARTIAL:
                    return new FlowError(Files::getUploadErrorMessage($source['error']), 1264440823);
                default:
                    $this->systemLogger->log(sprintf('A server error occurred while converting an uploaded resource: "%s"', Files::getUploadErrorMessage($source['error'])), LOG_ERR);
                    return new FlowError('An error occurred while uploading. Please try again or contact the administrator if the problem remains', 1340193849);
            }
        }

        if (isset($this->convertedResources[$source['tmp_name']])) {
            return $this->convertedResources[$source['tmp_name']];
        }

        try {
            $resource = $this->resourceManager->importUploadedResource($source, $this->getCollectionName($source, $configuration));
            $this->convertedResources[$source['tmp_name']] = $resource;
            return $resource;
        } catch (\Exception $exception) {
            $this->systemLogger->log('Could not import an uploaded file', LOG_WARNING);
            $this->systemLogger->logException($exception);
            return new FlowError('During import of an uploaded file an error occurred. See log for more details.', 1264517906);
        }
    }

    /**
     * @param array $source
     * @param PropertyMappingConfigurationInterface $configuration
     * @return PersistentResource|FlowError
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function handleHashAndData(array $source, PropertyMappingConfigurationInterface $configuration = null)
    {
        $hash = null;
        $resource = false;
        $givenResourceIdentity = null;
        if (isset($source['__identity'])) {
            $givenResourceIdentity = $source['__identity'];
            unset($source['__identity']);
            $resource = $this->resourceRepository->findByIdentifier($givenResourceIdentity);
            if ($resource instanceof PersistentResource) {
                return $resource;
            }

            if ($configuration->getConfigurationValue(ResourceTypeConverter::class, self::CONFIGURATION_IDENTITY_CREATION_ALLOWED) !== true) {
                throw new InvalidPropertyMappingConfigurationException('Creation of resource objects with identity not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_IDENTITY_CREATION_ALLOWED" to TRUE');
            }
        }

        if (isset($source['hash']) && preg_match('/[0-9a-f]{40}/', $source['hash'])) {
            $hash = $source['hash'];
        }

        if ($hash !== null && count($source) === 1) {
            $resource = $this->resourceManager->getResourceBySha1($hash);
        }
        if ($resource === null) {
            $collectionName = $this->getCollectionName($source, $configuration);
            if (isset($source['data'])) {
                $resource = $this->resourceManager->importResourceFromContent($source['data'], $source['filename'], $collectionName, $givenResourceIdentity);
            } elseif ($hash !== null) {
                /** @var PersistentResource $resource */
                $resource = $this->resourceManager->importResource($configuration->getConfigurationValue(ResourceTypeConverter::class, self::CONFIGURATION_RESOURCE_LOAD_PATH) . '/' . $hash, $collectionName, $givenResourceIdentity);
                if (is_array($source) && isset($source['filename'])) {
                    $resource->setFilename($source['filename']);
                }
            }
        }

        if ($resource instanceof PersistentResource) {
            return $resource;
        } else {
            return new FlowError('The resource manager could not create a Resource instance.', 1404312901);
        }
    }

    /**
     * Get the collection name this resource will be stored in. Default will be ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME
     * The propertyMappingConfiguration CONFIGURATION_COLLECTION_NAME will directly override the default. Then if CONFIGURATION_ALLOW_COLLECTION_OVERRIDE is TRUE
     * and __collectionName is in the $source this will finally be the value.
     *
     * @param array $source
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getCollectionName($source, PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME;
        }
        $collectionName = $configuration->getConfigurationValue(ResourceTypeConverter::class, self::CONFIGURATION_COLLECTION_NAME) ?: ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME;
        if (isset($source['__collectionName']) && $source['__collectionName'] !== '') {
            $collectionName = $source['__collectionName'];
        }

        if ($this->resourceManager->getCollection($collectionName) === null) {
            throw new InvalidPropertyMappingConfigurationException(sprintf('The selected resource collection named "%s" does not exist, a resource could not be imported.', $collectionName), 1416687475);
        }

        return $collectionName;
    }
}

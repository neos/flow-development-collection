<?php
namespace Neos\Flow\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Http\Message\UploadedFileInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error as FlowError;
use Neos\Http\Factories\FlowUploadedFile;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Utility\Files;
use Psr\Log\LoggerInterface;

/**
 * A type converter for converting strings, array and uploaded files to PersistentResource objects.
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
 *    the converter will look up an existing PersistentResource with that hash and return it if found. If that fails,
 *    the converter will try to import a file named like that hash from the configured CONFIGURATION_RESOURCE_LOAD_PATH.
 *
 *    If no hash is given in an array source but the key 'data' is set, the content of that key is assumed a binary string
 *    and a PersistentResource representing this content is created and returned.
 *
 *    The imported PersistentResource will be given a 'filename' if set in the source array in both cases (import from file or data).
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
     * Sets the default resource collection name (see Settings: Neos.Flow.resource.collections) to use for this resource,
     * will fallback to ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME
     *
     * @var string
     */
    const CONFIGURATION_COLLECTION_NAME = 'collectionName';

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string', 'array', UploadedFileInterface::class];

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
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $convertedResources = [];

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
     * @param array|string|UploadedFileInterface $source The upload info (expected keys: error, name, tmp_name), the hash or an UploadedFile
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return PersistentResource|null|FlowError if the input format is not supported or could not be converted for other reasons
     * @throws Exception
     * @throws Exception\InvalidResourceDataException
     * @throws InvalidPropertyMappingConfigurationException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (empty($source)) {
            return null;
        }

        if ($source instanceof UploadedFileInterface) {
            return $this->handleUploadedFile($source, $configuration);
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
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return PersistentResource|null|FlowError
     */
    protected function handleFileUploads(array $source, PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!isset($source['error']) || $source['error'] === \UPLOAD_ERR_NO_FILE) {
            if (isset($source['originallySubmittedResource']) && isset($source['originallySubmittedResource']['__identity'])) {
                /** @var PersistentResource|null $resource */
                $resource = $this->persistenceManager->getObjectByIdentifier($source['originallySubmittedResource']['__identity'], PersistentResource::class);
                return $resource;
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
                    $this->logger->error(sprintf('A server error occurred while converting an uploaded resource: "%s"', Files::getUploadErrorMessage($source['error'])), LogEnvironment::fromMethodName(__METHOD__));
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
            $this->logger->warning('Could not import an uploaded file', ['exception' => $exception] + LogEnvironment::fromMethodName(__METHOD__));
            return new FlowError('During import of an uploaded file an error occurred. See log for more details.', 1264517906);
        }
    }

    /**
     * @param array $source
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return PersistentResource|FlowError
     * @throws Exception
     * @throws Exception\InvalidResourceDataException
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

            if ($configuration === null || $configuration->getConfigurationValue(ResourceTypeConverter::class, self::CONFIGURATION_IDENTITY_CREATION_ALLOWED) !== true) {
                throw new InvalidPropertyMappingConfigurationException('Creation of resource objects with identity not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_IDENTITY_CREATION_ALLOWED" to true');
            }
        }

        if (isset($source['hash']) && preg_match('/[0-9a-f]{40}/', $source['hash'])) {
            $hash = $source['hash'];
        }

        if ($hash !== null && count($source) === 1) {
            $resource = $this->resourceManager->getResourceBySha1($hash);
        }
        if ($resource === null) {
            $collectionName = $source['collectionName'] ?? $this->getCollectionName($source, $configuration);
            if (isset($source['data'])) {
                $resource = $this->resourceManager->importResourceFromContent(base64_decode($source['data']), $source['filename'], $collectionName, $givenResourceIdentity);
            } elseif ($hash !== null) {
                $resource = $this->resourceManager->importResource($configuration->getConfigurationValue(ResourceTypeConverter::class, self::CONFIGURATION_RESOURCE_LOAD_PATH) . '/' . $hash, $collectionName, $givenResourceIdentity);
                if (is_array($source) && isset($source['filename'])) {
                    $resource->setFilename($source['filename']);
                }
            }
            if ($hash !== null && $resource->getSha1() !== $hash) {
                throw new Exception\InvalidResourceDataException('The source SHA1 did not match the SHA1 of the imported resource.', 1482248149);
            }
        }

        if ($resource instanceof PersistentResource) {
            return $resource;
        }

        return new FlowError('The resource manager could not create a PersistentResource instance.', 1404312901);
    }

    /**
     * @param UploadedFileInterface $source
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return PersistentResource|null|FlowError
     */
    protected function handleUploadedFile(UploadedFileInterface $source, PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source instanceof FlowUploadedFile && $source->getError() === UPLOAD_ERR_NO_FILE && $source->getOriginallySubmittedResource() !== null) {
            $identifier = is_array($source->getOriginallySubmittedResource()) ? $source->getOriginallySubmittedResource()['__identity'] : $source->getOriginallySubmittedResource();
            /** @var PersistentResource|null $resource */
            $resource = $this->persistenceManager->getObjectByIdentifier($identifier, PersistentResource::class);
            return $resource;
        }

        switch ($source->getError()) {
            case \UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return null;
            case \UPLOAD_ERR_INI_SIZE:
            case \UPLOAD_ERR_FORM_SIZE:
            case \UPLOAD_ERR_PARTIAL:
                return new FlowError(Files::getUploadErrorMessage($source->getError()), 1264440823);
            default:
                $this->logger->error(sprintf('A server error occurred while converting an uploaded resource: "%s"', Files::getUploadErrorMessage($source['error'])), LogEnvironment::fromMethodName(__METHOD__));

                return new FlowError('An error occurred while uploading. Please try again or contact the administrator if the problem remains', 1340193849);
        }

        if (isset($this->convertedResources[spl_object_hash($source)])) {
            return $this->convertedResources[spl_object_hash($source)];
        }

        try {
            $resource = $this->resourceManager->importResource($source->getStream()->detach(), $this->getCollectionName($source, $configuration));
            $resource->setFilename($source->getClientFilename());
            $this->convertedResources[spl_object_hash($source)] = $resource;
            return $resource;
        } catch (\Exception $exception) {
            $this->logger->warning('Could not import an uploaded file', ['exception' => $exception] + LogEnvironment::fromMethodName(__METHOD__));

            return new FlowError('During import of an uploaded file an error occurred. See log for more details.', 1264517906);
        }
    }


    /**
     * Get the collection name this resource will be stored in. Default will be ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME
     * The propertyMappingConfiguration CONFIGURATION_COLLECTION_NAME will directly override the default. Then if CONFIGURATION_ALLOW_COLLECTION_OVERRIDE is true
     * and __collectionName is in the $source this will finally be the value.
     *
     * @param array|UploadedFileInterface $source
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return string
     * @throws InvalidPropertyMappingConfigurationException
     */
    protected function getCollectionName($source, PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME;
        }
        $collectionName = $configuration->getConfigurationValue(ResourceTypeConverter::class, self::CONFIGURATION_COLLECTION_NAME) ?: ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME;

        if ($source instanceof FlowUploadedFile && $source->getCollectionName() !== null) {
            $collectionName = $source->getCollectionName();
        }

        if (is_array($source) && isset($source['__collectionName']) && $source['__collectionName'] !== '') {
            $collectionName = $source['__collectionName'];
        }

        if ($this->resourceManager->getCollection($collectionName) === null) {
            throw new InvalidPropertyMappingConfigurationException(sprintf('The selected resource collection named "%s" does not exist, a resource could not be imported.', $collectionName), 1416687475);
        }

        return $collectionName;
    }
}

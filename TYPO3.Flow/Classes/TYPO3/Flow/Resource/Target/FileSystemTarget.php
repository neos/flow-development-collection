<?php
namespace TYPO3\Flow\Resource\Target;

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
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Publishing\MessageCollector;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceMetaDataInterface;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * A target which publishes resources to a specific directory in a file system.
 */
class FileSystemTarget implements TargetInterface
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * Name which identifies this publishing target
     *
     * @var string
     */
    protected $name;

    /**
     * The path (in a filesystem) where resources are published to
     *
     * @var string
     */
    protected $path;

    /**
     * The configured publicly accessible web URI which points to the root path of this target.
     * Can be relative to website's base Uri, for example "_Resources/MySpecialTarget/".
     * If resources should be served from a different domain, make sure to specify an absolute URI though
     *
     * @var string
     */
    protected $baseUri = '';

    /**
     * The configured TYPO3.Flow.http.baseUri to use as fallback if no absolute baseUri is configured
     * and if it can't be determined from the current request (e.g. in CLI mode)
     *
     * @Flow\InjectConfiguration(package="TYPO3.Flow", path="http.baseUri")
     * @var string
     */
    protected $httpBaseUri;

    /**
     * The resolved absolute web URI for this target. If $baseUri was absolute this will be the same,
     * otherwise the request base uri will be prepended.
     *
     * @var string
     */
    protected $absoluteBaseUri;

    /**
     * If the generated URI path segment containing the sha1 should be divided into multiple segments
     *
     * @var boolean
     */
    protected $subdivideHashPathSegment = true;

    /**
     * A list of extensions that are blacklisted and must not be published by this target.
     *
     * @var array
     */
    protected $extensionBlacklist = [];

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Core\Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var MessageCollector
     */
    protected $messageCollector;

    /**
     * Constructor
     *
     * @param string $name Name of this target instance, according to the resource settings
     * @param array $options Options for this target
     */
    public function __construct($name, array $options = array())
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Initializes this resource publishing target
     *
     * @return void
     * @throws \TYPO3\Flow\Resource\Exception
     */
    public function initializeObject()
    {
        foreach ($this->options as $key => $value) {
            $isOptionSet = $this->setOption($key, $value);
            if (!$isOptionSet) {
                throw new Exception(sprintf('An unknown option "%s" was specified in the configuration of a resource FileSystemTarget. Please check your settings.', $key), 1361525952);
            }
        }

        if (!is_writable($this->path)) {
            @Files::createDirectoryRecursively($this->path);
        }
        if (!is_dir($this->path) && !is_link($this->path)) {
            throw new Exception('The directory "' . $this->path . '" which was configured as a publishing target does not exist and could not be created.', 1207124538);
        }
        if (!is_writable($this->path)) {
            throw new Exception('The directory "' . $this->path . '" which was configured as a publishing target is not writable.', 1207124546);
        }
    }

    /**
     * Returns the name of this target instance
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Publishes the whole collection to this target
     *
     * @param CollectionInterface $collection The collection to publish
     * @param callable $callback Function called after each resource publishing
     * @return void
     * @throws Exception
     */
    public function publishCollection(CollectionInterface $collection, callable $callback = null)
    {
        foreach ($collection->getObjects($callback) as $object) {
            /** @var \TYPO3\Flow\Resource\Storage\Object $object */
            $sourceStream = $object->getStream();
            if ($sourceStream === false) {
                $this->handleMissingData($object, $collection);
                continue;
            }
            $this->publishFile($sourceStream, $this->getRelativePublicationPathAndFilename($object));
            fclose($sourceStream);
        }
    }

    /**
     * Publishes the given persistent resource from the given storage
     *
     * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
     * @param CollectionInterface $collection The collection the given resource belongs to
     * @return void
     * @throws Exception
     */
    public function publishResource(Resource $resource, CollectionInterface $collection)
    {
        $sourceStream = $resource->getStream();
        if ($sourceStream === false) {
            $this->handleMissingData($resource, $collection);
            return;
        }
        $this->publishFile($sourceStream, $this->getRelativePublicationPathAndFilename($resource));
        fclose($sourceStream);
    }

    /**
     * Handle missing data notification
     *
     * @param CollectionInterface $collection
     * @param ResourceMetaDataInterface $resource
     */
    protected function handleMissingData(ResourceMetaDataInterface $resource, CollectionInterface $collection)
    {
        $message = sprintf('Could not publish resource %s with SHA1 hash %s of collection %s because there seems to be no corresponding data in the storage.', $resource->getFilename(), $resource->getSha1(), $collection->getName());
        $this->messageCollector->append($message);
    }

    /**
     * Unpublishes the given persistent resource
     *
     * @param \TYPO3\Flow\Resource\Resource $resource The resource to unpublish
     * @return void
     */
    public function unpublishResource(Resource $resource)
    {
        $resources = $this->resourceRepository->findSimilarResources($resource);
        if (count($resources) > 1) {
            return;
        }
        $this->unpublishFile($this->getRelativePublicationPathAndFilename($resource));
    }

    /**
     * Returns the web accessible URI pointing to the given static resource
     *
     * @param string $relativePathAndFilename Relative path and filename of the static resource
     * @return string The URI
     */
    public function getPublicStaticResourceUri($relativePathAndFilename)
    {
        return $this->getResourcesBaseUri() . $this->encodeRelativePathAndFilenameForUri($relativePathAndFilename);
    }

    /**
     * Returns the web accessible URI pointing to the specified persistent resource
     *
     * @param \TYPO3\Flow\Resource\Resource $resource Resource object
     * @return string The URI
     * @throws Exception
     */
    public function getPublicPersistentResourceUri(Resource $resource)
    {
        return $this->getResourcesBaseUri() . $this->encodeRelativePathAndFilenameForUri($this->getRelativePublicationPathAndFilename($resource));
    }

    /**
     * Applies rawurlencode() to all path segments of the given $relativePathAndFilename
     *
     * @param string $relativePathAndFilename
     * @return string
     */
    protected function encodeRelativePathAndFilenameForUri($relativePathAndFilename)
    {
        return implode('/', array_map('rawurlencode', explode('/', $relativePathAndFilename)));
    }

    /**
     * Publishes the given source stream to this target, with the given relative path.
     *
     * @param resource $sourceStream Stream of the source to publish
     * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
     * @return void
     * @throws Exception
     * @throws \Exception
     * @throws \TYPO3\Flow\Utility\Exception
     */
    protected function publishFile($sourceStream, $relativeTargetPathAndFilename)
    {
        $pathInfo = UnicodeFunctions::pathinfo($relativeTargetPathAndFilename);
        if (isset($pathInfo['extension']) && array_key_exists(strtolower($pathInfo['extension']), $this->extensionBlacklist) && $this->extensionBlacklist[strtolower($pathInfo['extension'])] === true) {
            throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the filename extension "%s" is blacklisted.', $sourceStream, $this->name, strtolower($pathInfo['extension'])), 1447148472);
        }

        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

        if (@fstat($sourceStream) === false) {
            throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file is not accessible (file stat failed).', $sourceStream, $this->name), 1375258499);
        }

        if (!file_exists(dirname($targetPathAndFilename))) {
            Files::createDirectoryRecursively(dirname($targetPathAndFilename));
        }

        if (!is_writable(dirname($targetPathAndFilename))) {
            throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the target file "%s" is not writable.', $sourceStream, $this->name, $targetPathAndFilename), 1428917322, (isset($exception) ? $exception : null));
        }

        try {
            $targetFileHandle = fopen($targetPathAndFilename, 'w');
            $result = stream_copy_to_stream($sourceStream, $targetFileHandle);
            fclose($targetFileHandle);
        } catch (\Exception $exception) {
            $result = false;
        }
        if ($result === false) {
            throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file could not be copied to the target location.', $sourceStream, $this->name), 1375258399, (isset($exception) ? $exception : null));
        }

        $this->systemLogger->log(sprintf('FileSystemTarget: Published file. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename), LOG_DEBUG);
    }

    /**
     * Removes the specified target file from the public directory
     *
     * This method fails silently if the given file could not be unpublished or already didn't exist anymore.
     *
     * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
     * @return void
     */
    protected function unpublishFile($relativeTargetPathAndFilename)
    {
        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;
        if (!file_exists($targetPathAndFilename)) {
            return;
        }
        if (!Files::unlink($targetPathAndFilename)) {
            return;
        }
        Files::removeEmptyDirectoriesOnPath(dirname($targetPathAndFilename));
    }

    /**
     * Returns the resolved absolute base URI for resources of this target.
     *
     * @return string The absolute base URI for resources in this target
     */
    protected function getResourcesBaseUri()
    {
        if ($this->absoluteBaseUri === null) {
            $this->absoluteBaseUri = $this->detectResourcesBaseUri();
        }

        return $this->absoluteBaseUri;
    }

    /**
     * Detects and returns the website's absolute base URI
     *
     * @return string The resolved resource base URI, @see getResourcesBaseUri()
     * @throws Exception if the baseUri can't be resolved
     */
    protected function detectResourcesBaseUri()
    {
        if ($this->baseUri !== '' && ($this->baseUri[0] === '/' || strpos($this->baseUri, '://') !== false)) {
            return $this->baseUri;
        }

        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($requestHandler instanceof HttpRequestHandlerInterface) {
            return $requestHandler->getHttpRequest()->getBaseUri() . $this->baseUri;
        }

        if ($this->httpBaseUri === null) {
            throw new Exception(sprintf('The base URI for resources could not be detected. Please specify the "TYPO3.Flow.http.baseUri" setting or use an absolute "baseUri" option for target "%s".', $this->name), 1438093977);
        }
        return $this->httpBaseUri . $this->baseUri;
    }

    /**
     * Determines and returns the relative path and filename for the given Storage Object or Resource. If the given
     * object represents a persistent resource, its own relative publication path will be empty. If the given object
     * represents a static resources, it will contain a relative path.
     *
     * No matter which kind of resource, persistent or static, this function will return a sub directory structure
     * if no relative publication path was defined in the given object.
     *
     * @param ResourceMetaDataInterface $object Resource or Storage Object
     * @return string The relative path and filename, for example "c/8/2/8/c828d0f88ce197be1aff7cc2e5e86b1244241ac6/MyPicture.jpg" (if subdivideHashPathSegment is on) or "c828d0f88ce197be1aff7cc2e5e86b1244241ac6/MyPicture.jpg" (if it's off)
     */
    protected function getRelativePublicationPathAndFilename(ResourceMetaDataInterface $object)
    {
        if ($object->getRelativePublicationPath() !== '') {
            return $object->getRelativePublicationPath() . $object->getFilename();
        }

        if ($this->subdivideHashPathSegment) {
            $sha1Hash = $object->getSha1();
            $pathAndFilename = $sha1Hash[0] . '/' . $sha1Hash[1] . '/' . $sha1Hash[2] . '/' . $sha1Hash[3] . '/' . $sha1Hash . '/' . $object->getFilename();
        } else {
            $pathAndFilename = $object->getSha1() . '/' . $object->getFilename();
        }
        return $pathAndFilename;
    }

    /**
     * Set an option value and return if it was set.
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    protected function setOption($key, $value)
    {
        switch ($key) {
            case 'baseUri':
            case 'path':
            case 'extensionBlacklist':
                $this->$key = $value;
                break;
            case 'subdivideHashPathSegment':
                $this->subdivideHashPathSegment = (boolean)$value;
                break;
            default:
                return false;
        }

        return true;
    }
}

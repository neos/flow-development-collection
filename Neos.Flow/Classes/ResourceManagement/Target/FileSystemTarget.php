<?php
namespace Neos\Flow\ResourceManagement\Target;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\Publishing\MessageCollector;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceMetaDataInterface;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\ResourceManagement\Storage\StorageObject;
use Neos\Utility\Files;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;
use Neos\Flow\Utility\Exception as UtilityException;
use Neos\Flow\ResourceManagement\Target\Exception as TargetException;

/**
 * A target which publishes resources to a specific directory in a file system.
 */
class FileSystemTarget implements TargetInterface
{
    /**
     * @var array
     */
    protected $options = [];

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
     * The configured Neos.Flow.http.baseUri to use as fallback if no absolute baseUri is configured
     * and if it can't be determined from the current request (e.g. in CLI mode)
     *
     * @Flow\InjectConfiguration(package="Neos.Flow", path="http.baseUri")
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
     * @var ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
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
    public function __construct($name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Initializes this resource publishing target
     *
     * @return void
     * @throws TargetException
     */
    public function initializeObject()
    {
        foreach ($this->options as $key => $value) {
            $isOptionSet = $this->setOption($key, $value);
            if (!$isOptionSet) {
                throw new TargetException(sprintf('An unknown option "%s" was specified in the configuration of a resource FileSystemTarget. Please check your settings.', $key), 1361525952);
            }
        }

        if (!is_writable($this->path)) {
            @Files::createDirectoryRecursively($this->path);
        }
        if (!is_dir($this->path) && !is_link($this->path)) {
            throw new TargetException('The directory "' . $this->path . '" which was configured as a publishing target does not exist and could not be created.', 1207124538);
        }
        if (!is_writable($this->path)) {
            throw new TargetException('The directory "' . $this->path . '" which was configured as a publishing target is not writable.', 1207124546);
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
     */
    public function publishCollection(CollectionInterface $collection, callable $callback = null)
    {
        foreach ($collection->getObjects($callback) as $object) {
            /** @var StorageObject $object */
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
     * @param PersistentResource $resource The resource to publish
     * @param CollectionInterface $collection The collection the given resource belongs to
     * @return void
     */
    public function publishResource(PersistentResource $resource, CollectionInterface $collection)
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
     * @param PersistentResource $resource The resource to unpublish
     * @return void
     */
    public function unpublishResource(PersistentResource $resource)
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
     * @param PersistentResource $resource PersistentResource object
     * @return string The URI
     * @throws Exception
     */
    public function getPublicPersistentResourceUri(PersistentResource $resource)
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
     * @throws TargetException
     */
    protected function publishFile($sourceStream, $relativeTargetPathAndFilename)
    {
        $pathInfo = UnicodeFunctions::pathinfo($relativeTargetPathAndFilename);
        if (isset($pathInfo['extension']) && array_key_exists(strtolower($pathInfo['extension']), $this->extensionBlacklist) && $this->extensionBlacklist[strtolower($pathInfo['extension'])] === true) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the filename extension "%s" is blacklisted.', $sourceStream, $this->name, strtolower($pathInfo['extension'])), 1447148472);
        }

        $targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

        if (@fstat($sourceStream) === false) {
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file is not accessible (file stat failed).', $sourceStream, $this->name), 1375258499);
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
            throw new TargetException(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file could not be copied to the target location.', $sourceStream, $this->name), 1375258399, (isset($exception) ? $exception : null));
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
     * @throws TargetException if the baseUri can't be resolved
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
            throw new TargetException(sprintf('The base URI for resources could not be detected. Please specify the "Neos.Flow.http.baseUri" setting or use an absolute "baseUri" option for target "%s".', $this->name), 1438093977);
        }
        return $this->httpBaseUri . $this->baseUri;
    }

    /**
     * Determines and returns the relative path and filename for the given Storage Object or PersistentResource. If the given
     * object represents a persistent resource, its own relative publication path will be empty. If the given object
     * represents a static resources, it will contain a relative path.
     *
     * No matter which kind of resource, persistent or static, this function will return a sub directory structure
     * if no relative publication path was defined in the given object.
     *
     * @param ResourceMetaDataInterface $object PersistentResource or Storage Object
     * @return string The relative path and filename, for example "c/8/2/8/c828d0f88ce197be1aff7cc2e5e86b1244241ac6/MyPicture.jpg" (if subdivideHashPathSegment is on) or "c828d0f88ce197be1aff7cc2e5e86b1244241ac6/MyPicture.jpg" (if it's off)
     */
    protected function getRelativePublicationPathAndFilename(ResourceMetaDataInterface $object)
    {
        if ($object->getRelativePublicationPath() !== '') {
            $pathAndFilename = $object->getRelativePublicationPath() . $object->getFilename();
        } else {
            if ($this->subdivideHashPathSegment) {
                $sha1Hash = $object->getSha1();
                $pathAndFilename = $sha1Hash[0] . '/' . $sha1Hash[1] . '/' . $sha1Hash[2] . '/' . $sha1Hash[3] . '/' . $sha1Hash . '/' . $object->getFilename();
            } else {
                $pathAndFilename = $object->getSha1() . '/' . $object->getFilename();
            }
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

<?php
namespace TYPO3\Flow\Resource\Target;

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
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Resource\Collection;
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceMetaDataInterface;
use TYPO3\Flow\Utility\Files;

/**
 * A target which publishes resources to a specific directory in a file system.
 */
class FileSystemTarget implements TargetInterface {

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
	 * Publicly accessible web URI which points to the root path of this target.
	 * Can be relative to website's base Uri, for example "_Resources/MySpecialTarget/"
	 *
	 * @var string
	 */
	protected $baseUri = '';

	/**
	 * If the generated URI path segment containing the sha1 should be divided into multiple segments
	 *
	 * @var boolean
	 */
	protected $subdivideHashPathSegment = TRUE;

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
	 * Constructor
	 *
	 * @param string $name Name of this target instance, according to the resource settings
	 * @param array $options Options for this target
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
		$this->options = $options;
	}

	/**
	 * Initializes this resource publishing target
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Resource\Exception
	 */
	public function initializeObject() {
		foreach ($this->options as $key => $value) {
			switch ($key) {
				case 'baseUri':
					if (strpos($value, '://') === FALSE && $value[0] !== '/') {
						$this->baseUri = $this->detectResourcesBaseUri() . $value;
					} else {
						$this->baseUri = $value;
					}
					break;
				case 'path':
					$this->$key = $value;
					break;
				case 'subdivideHashPathSegment':
					$this->subdivideHashPathSegment = (boolean)$value;
					break;
				default:
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
	public function getName() {
		return $this->name;
	}

	/**
	 * Publishes the whole collection to this target
	 *
	 * @param \TYPO3\Flow\Resource\Collection $collection The collection to publish
	 * @return void
	 * @throws Exception
	 */
	public function publishCollection(Collection $collection) {
		foreach ($collection->getObjects() as $object) {
			/** @var \TYPO3\Flow\Resource\Storage\Object $object */
			$sourceStream = $object->getStream();
			if ($sourceStream === FALSE) {
				throw new Exception(sprintf('Could not publish resource %s with SHA1 hash %s of collection %s because there seems to be no corresponding data in the storage.', $object->getFilename(), $object->getSha1(), $collection->getName()), 1417168142);
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
	public function publishResource(Resource $resource, CollectionInterface $collection) {
		$sourceStream = $resource->getStream();
		if ($sourceStream === FALSE) {
			throw new Exception(sprintf('Could not publish resource %s with SHA1 hash %s of collection %s because there seems to be no corresponding data in the storage.', $resource->getFilename(), $resource->getSha1(), $collection->getName()), 1375258146);
		}
		$this->publishFile($sourceStream, $this->getRelativePublicationPathAndFilename($resource));
		fclose($sourceStream);
	}

	/**
	 * Unpublishes the given persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to unpublish
	 * @return void
	 */
	public function unpublishResource(Resource $resource) {
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
	public function getPublicStaticResourceUri($relativePathAndFilename) {
		return $this->baseUri . $relativePathAndFilename;
	}

	/**
	 * Returns the web accessible URI pointing to the specified persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource Resource object
	 * @return string The URI
	 * @throws Exception
	 */
	public function getPublicPersistentResourceUri(Resource $resource) {
		return $this->baseUri . $this->getRelativePublicationPathAndFilename($resource);
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
	protected function publishFile($sourceStream, $relativeTargetPathAndFilename) {
		$targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

		if (@fstat($sourceStream) === FALSE) {
			throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file is not accessible (file stat failed).', $sourceStream, $this->name), 1375258499);
		}

		if (!file_exists(dirname($targetPathAndFilename))) {
			Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		try {
			$targetFileHandle = fopen($targetPathAndFilename, 'w');
			$result = stream_copy_to_stream($sourceStream, $targetFileHandle);
			fclose($targetFileHandle);
		} catch (\Exception $exception) {
			$result = FALSE;
		}
		if ($result === FALSE) {
			throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file could not be copied to the target location.', $sourceStream, $this->name), 1375258399, (isset($exception) ? $exception : NULL));
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
	protected function unpublishFile($relativeTargetPathAndFilename) {
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
	 * Detects and returns the website's base URI
	 *
	 * @return string The website's base URI
	 */
	protected function detectResourcesBaseUri() {
		$uri = '';
		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof HttpRequestHandlerInterface) {
			// In functional tests or some other obscure scenarios we might end up without a current HTTP request:
			$request = $requestHandler->getHttpRequest();
			if ($request instanceof Request) {
				$uri = $requestHandler->getHttpRequest()->getBaseUri();
			}
		}
		return (string)$uri;
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
	 * @return string The relative path and filename, for example "c828d/0f88c/e197b/e1aff/7cc2e/5e86b/12442/41ac6/MyPicture.jpg" (if subdivideHashPathSegment is on) or "c828d0f88ce197be1aff7cc2e5e86b1244241ac6/MyPicture.jpg" (if it's off)
	 */
	protected function getRelativePublicationPathAndFilename(ResourceMetaDataInterface $object) {
		if ($object->getRelativePublicationPath() !== '') {
			$pathAndFilename = $object->getRelativePublicationPath() . $object->getFilename();
		} else {
			if ($this->subdivideHashPathSegment) {
				$pathAndFilename = wordwrap($object->getSha1(), 5, '/', TRUE) . '/' . $object->getFilename();
			} else {
				$pathAndFilename = $object->getSha1() . '/' . $object->getFilename();
			}
		}
		return $pathAndFilename;
	}
}

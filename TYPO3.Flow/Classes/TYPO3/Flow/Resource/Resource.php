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
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * Model representing a persistable resource
 *
 * @Flow\Entity
 */
class Resource implements ResourceMetaDataInterface, CacheAwareInterface {

	/**
	 * Name of a collection whose storage is used for storing this resource and whose
	 * target is used for publishing.
	 *
	 * @var string
	 */
	protected $collectionName = ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME;

	/**
	 * Filename which is used when the data of this resource is downloaded as a file or acting as a label
	 *
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
	 * @ORM\Column(length=255)
	 */
	protected $filename = '';

	/**
	 * The size of this object's data
	 *
	 * @var integer
	 * @ORM\Column(type="decimal", scale=0, precision=20, nullable=false)
	 */
	protected $fileSize;

	/**
	 * An optional relative path which can be used by a publishing target for structuring resources into directories
	 *
	 * @var string
	 */
	protected $relativePublicationPath = '';

	/**
	 * The IANA media type of this resource
	 *
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=100 })
	 * @ORM\Column(length=100)
	 */
	protected $mediaType;

	/**
	 * SHA1 hash identifying the content attached to this resource
	 *
	 * @var string
	 * @ORM\Column(length=40)
	 */
	protected $sha1;

	/**
	 * MD5 hash identifying the content attached to this resource
	 *
	 * @var string
	 * @ORM\Column(length=32)
	 */
	protected $md5;

	/**
	 * As soon as the Resource has been published, modifying this object is not allowed
	 *
	 * @Flow\Transient
	 * @var boolean
	 */
	protected $protected = FALSE;

	/**
	 * @Flow\Transient
	 * @var boolean
	 */
	protected $lifecycleEventsActive = TRUE;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @Flow\Transient
	 * @var string
	 */
	protected $temporaryLocalCopyPathAndFilename;

	/**
	 * Protects this Resource if it has been persisted already.
	 *
	 * @param integer $initializationCause
	 * @return void
	 */
	public function initializeObject($initializationCause) {
		if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED) {
			$this->protected = TRUE;
		}
	}

	/**
	 * Returns a stream for use with read-only file operations such as reading or copying.
	 *
	 * @return resource | boolean A stream which points to the data of this resource for read-access or FALSE if the stream could not be obtained
	 * @api
	 */
	public function getStream() {
		return $this->resourceManager->getStreamByResource($this);
	}

	/**
	 * Sets the name of the collection this resource should be part of
	 *
	 * @param string $collectionName Name of the collection
	 * @return void
	 * @api
	 */
	public function setCollectionName($collectionName) {
		$this->throwExceptionIfProtected();
		$this->collectionName = $collectionName;
	}

	/**
	 * Returns the name of the collection this resource is part of
	 *
	 * @return string Name of the collection, for example "persistentResources"
	 * @api
	 */
	public function getCollectionName() {
		return $this->collectionName;
	}

	/**
	 * Sets the filename which is used when this resource is downloaded or saved as a file
	 *
	 * @param string $filename
	 * @return void
	 * @api
	 */
	public function setFilename($filename) {
		$this->throwExceptionIfProtected();

		$pathInfo = UnicodeFunctions::pathinfo($filename);
		$extension = (isset($pathInfo['extension']) ? '.' . strtolower($pathInfo['extension']) : '');
		$this->filename = $pathInfo['filename'] . $extension;
		$this->mediaType = MediaTypes::getMediaTypeFromFilename($this->filename);
	}

	/**
	 * Gets the filename
	 *
	 * @return string The filename
	 * @api
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Returns the file extension used for this resource
	 *
	 * @return string The file extension used for this file
	 * @api
	 */
	public function getFileExtension() {
		$pathInfo = pathInfo($this->filename);
		return isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
	}

	/**
	 * Sets a relative path which can be used by a publishing target for structuring resources into directories
	 *
	 * @param string $path
	 * @return void
	 * @api
	 */
	public function setRelativePublicationPath($path) {
		$this->throwExceptionIfProtected();
		$this->relativePublicationPath = $path;
	}

	/**
	 * Returns the relative publication path
	 *
	 * @return string
	 * @api
	 */
	public function getRelativePublicationPath() {
		return $this->relativePublicationPath;
	}

	/**
	 * Explicitly sets the Media Type for this resource
	 *
	 * @param string $mediaType The IANA Media Type
	 * @return void
	 * @api
	 */
	public function setMediaType($mediaType) {
		$this->mediaType = $mediaType;
	}

	/**
	 * Returns the Media Type for this resource
	 *
	 * @return string The IANA Media Type
	 * @api
	 */
	public function getMediaType() {
		if ($this->mediaType === NULL) {
			return MediaTypes::getMediaTypeFromFilename($this->filename);
		} else {
			return $this->mediaType;
		}
	}

	/**
	 * Sets the size of the content of this resource
	 *
	 * @param integer $fileSize The content size
	 * @return void
	 */
	public function setFileSize($fileSize) {
		$this->throwExceptionIfProtected();
		$this->fileSize = $fileSize;
	}

	/**
	 * Returns the size of the content of this resource
	 *
	 * @return integer The content size
	 */
	public function getFileSize() {
		return $this->fileSize;
	}

	/**
	 * Returns the sha1 hash of the content of this resource
	 *
	 * @return string The sha1 hash
	 * @api
	 * @deprecated please use getSha1() instead
	 */
	public function getHash() {
		return $this->sha1;
	}

	/**
	 * Sets the SHA1 hash of the content of this resource
	 *
	 * @param string $hash The sha1 hash
	 * @return void
	 * @api
	 * @deprecated please use setSha1() instead
	 */
	public function setHash($hash) {
		$this->setSha1($hash);
	}

	/**
	 * Returns the SHA1 hash of the content of this resource
	 *
	 * @return string The sha1 hash
	 * @api
	 */
	public function getSha1() {
		return $this->sha1;
	}

	/**
	 * Sets the SHA1 hash of the content of this resource
	 *
	 * @param string $sha1 The sha1 hash
	 * @return void
	 * @api
	 */
	public function setSha1($sha1) {
		$this->throwExceptionIfProtected();
		if (preg_match('/[a-f0-9]{40}/', $sha1) !== 1) {
			throw new \InvalidArgumentException('Specified invalid hash to setSha1()', 1362564220);
		}
		$this->sha1 = $sha1;
	}

	/**
	 * Returns the MD5 hash of the content of this resource
	 *
	 * @return string The MD5 hash
	 * @api
	 */
	public function getMd5() {
		return $this->md5;
	}

	/**
	 * Sets the MD5 hash of the content of this resource
	 *
	 * @param string $md5 The MD5 hash
	 * @return void
	 * @api
	 */
	public function setMd5($md5) {
		$this->throwExceptionIfProtected();
		$this->md5 = $md5;
	}

	/**
	 * Returns the path to a local file representing this resource for use with read-only file operations such as reading or copying.
	 *
	 * Note that you must not store or publish file paths returned from this method as they will change with every request.
	 *
	 * @return string Absolute path and filename pointing to the temporary local copy of this resource
	 * @throws Exception
	 * @api
	 */
	public function createTemporaryLocalCopy() {
		if ($this->temporaryLocalCopyPathAndFilename === NULL) {
			$temporaryPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'ResourceFiles/';
			try {
				Files::createDirectoryRecursively($temporaryPathAndFilename);
			} catch (\TYPO3\Flow\Utility\Exception $e) {
				throw new Exception(sprintf('Could not create the temporary directory %s while trying to create a temporary local copy of resource %s (%s).', $temporaryPathAndFilename, $this->sha1, $this->filename), 1416221864);
			}

			$temporaryPathAndFilename .= $this->getCacheEntryIdentifier();
			$temporaryPathAndFilename .= '-' . microtime(TRUE);

			if (function_exists('posix_getpid')) {
				$temporaryPathAndFilename .= '-' . str_pad(posix_getpid(), 10);
			} else {
				$temporaryPathAndFilename .= '-' . (string) getmypid();
			}

			$temporaryPathAndFilename = trim($temporaryPathAndFilename);
			$temporaryFileHandle = fopen($temporaryPathAndFilename, 'w');
			if ($temporaryFileHandle === FALSE) {
				throw new Exception(sprintf('Could not create the temporary file %s while trying to create a temporary local copy of resource %s (%s).', $temporaryPathAndFilename, $this->sha1, $this->filename), 1416221864);
			}
			$resourceStream = $this->getStream();
			if ($resourceStream === FALSE) {
				throw new Exception(sprintf('Could not open stream for resource %s ("%s") from collection "%s" while trying to create a temporary local copy.', $this->sha1, $this->filename, $this->collectionName), 1416221863);
			}
			stream_copy_to_stream($resourceStream, $temporaryFileHandle);
			$this->temporaryLocalCopyPathAndFilename = $temporaryPathAndFilename;
		}

		return $this->temporaryLocalCopyPathAndFilename;
	}

	/**
	 * Sets the resource pointer
	 *
	 * Deprecated – use setHash() instead!
	 *
	 * @param \TYPO3\Flow\Resource\ResourcePointer $resourcePointer
	 * @return void
	 * @deprecated
	 * @see setSha1()
	 */
	public function setResourcePointer(ResourcePointer $resourcePointer) {
		$this->throwExceptionIfProtected();
		$this->sha1 = $resourcePointer->getHash();
	}

	/**
	 * Returns the resource pointer
	 *
	 * Deprecated – use getHash() instead!
	 *
	 * @return \TYPO3\Flow\Resource\ResourcePointer $resourcePointer
	 * @api
	 * @deprecated
	 */
	public function getResourcePointer() {
		return new ResourcePointer($this->sha1);
	}

	/**
	 * Returns the SHA1 of the content this Resource is related to
	 *
	 * @return string
	 * @deprecated
	 */
	public function __toString() {
		return $this->sha1;
	}

	/**
	 * Doctrine lifecycle event callback which is triggered on "postPersist" events.
	 * This method triggers the publication of this resource.
	 *
	 * @return void
	 * @ORM\PostPersist
	 */
	public function postPersist() {
		if ($this->lifecycleEventsActive) {
			$collection = $this->resourceManager->getCollection($this->collectionName);
			$collection->getTarget()->publishResource($this, $collection);
		}
	}

	/**
	 * Doctrine lifecycle event callback which is triggered on "preRemove" events.
	 * This method triggers the deletion of data related to this resource.
	 *
	 * @return void
	 * @ORM\PreRemove
	 */
	public function preRemove() {
		if ($this->lifecycleEventsActive) {
			$this->resourceManager->deleteResource($this);
		}
	}

	/**
	 * A very internal function which disables the Doctrine lifecycle events for this Resource.
	 *
	 * This is needed when some low-level operations need to be done, for example deleting a Resource from the
	 * ResourceRepository without unpublishing the (probably not existing) data from the storage.
	 *
	 * @return void
	 */
	public function disableLifecycleEvents() {
		$this->lifecycleEventsActive = FALSE;
	}

	/**
	 * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
	 * related to this object. Introduced through the CacheAwareInterface.
	 *
	 * @return string
	 */
	public function getCacheEntryIdentifier() {
		return $this->sha1;
	}

	/**
	 * Throws an exception if this Resource object is protected against modifications.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function throwExceptionIfProtected() {
		if ($this->protected) {
			throw new Exception(sprintf('Tried to set a property of the resource object with SHA1 hash %s after it has been protected. Modifications are not allowed as soon as the Resource has been published or persisted.', $this->sha1), 1377852347);
		}
	}

	/**
	 * Takes care of removing a possibly existing temporary local copy on destruction of this object.
	 *
	 * Note: we can't use __destruct() here because this would lead Doctrine to create a proxy method __destruct() which
	 *       will run __load(), which in turn will triger the SQL protection in Flow Security, which will then discover
	 *       that a possibly previously existing session has been half-destroyed already (see FLOW-121).
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->temporaryLocalCopyPathAndFilename !== NULL) {
			unlink($this->temporaryLocalCopyPathAndFilename);
		}
	}
}

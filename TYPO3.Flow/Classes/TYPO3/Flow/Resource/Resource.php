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
use TYPO3\Flow\Utility\MediaTypes;

/**
 * Model describing a resource
 *
 * @Flow\Entity
 */
class Resource {

	/**
	 * @var \TYPO3\Flow\Resource\ResourcePointer
	 * @ORM\ManyToOne(cascade={"persist", "merge"})
	 */
	protected $resourcePointer;

	/**
	 * @var \TYPO3\Flow\Resource\Publishing\AbstractPublishingConfiguration
	 * @ORM\ManyToOne
	 */
	protected $publishingConfiguration;

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=100 })
	 */
	protected $filename = '';

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=100 })
	 */
	protected $fileExtension = '';

	/**
	 * Returns the SHA1 of the ResourcePointer this Resource uses.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->resourcePointer->__toString();
	}

	/**
	 * Returns a resource://<sha1> URI for use with file operations, …
	 *
	 * @return string
	 */
	public function getUri() {
		return 'resource://' . $this->resourcePointer;
	}

	/**
	 * Sets the filename
	 *
	 * @param string $filename
	 * @return void
	 */
	public function setFilename($filename) {
		$pathInfo = pathinfo($filename);
		if (isset($pathInfo['extension'])) {
			$this->fileExtension = strtolower($pathInfo['extension']);
		} else {
			$this->fileExtension = '';
		}
		$this->filename = $pathInfo['filename'];
		if ($this->fileExtension !== '') {
			$this->filename .= '.' . $this->fileExtension;
		}
	}

	/**
	 * Gets the filename
	 *
	 * @return string The filename
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Returns the file extension used for this resource
	 *
	 * @return string The file extension used for this file
	 */
	public function getFileExtension() {
		return $this->fileExtension;
	}

	/**
	 * Returns the mime type for this resource
	 *
	 * @return string The mime type
	 * @deprecated since 1.1.0
	 * @see getMediaType()
	 */
	public function getMimeType() {
		return $this->getMediaType();
	}

	/**
	 * Returns the Media Type for this resource
	 *
	 * @return string The IANA Media Type
	 */
	public function getMediaType() {
		return MediaTypes::getMediaTypeFromFilename('x.' . $this->getFileExtension());
	}

	/**
	 * Sets the resource pointer
	 *
	 * @param \TYPO3\Flow\Resource\ResourcePointer $resourcePointer
	 * @return void
	 */
	public function setResourcePointer(\TYPO3\Flow\Resource\ResourcePointer $resourcePointer) {
		$this->resourcePointer = $resourcePointer;
	}

	/**
	 * Returns the resource pointer
	 *
	 * @return \TYPO3\Flow\Resource\ResourcePointer $resourcePointer
	 */
	public function getResourcePointer() {
		return $this->resourcePointer;
	}

	/**
	 * Sets the publishing configuration for this resource
	 *
	 * @param \TYPO3\Flow\Resource\Publishing\PublishingConfigurationInterface $publishingConfiguration The publishing configuration
	 * @return void
	 */
	public function setPublishingConfiguration(\TYPO3\Flow\Resource\Publishing\PublishingConfigurationInterface $publishingConfiguration = NULL) {
		$this->publishingConfiguration = $publishingConfiguration;
	}

	/**
	 * Returns the publishing configuration for this resource
	 *
	 * @return \TYPO3\Flow\Resource\Publishing\PublishingConfigurationInterface The publishing configuration
	 */
	public function getPublishingConfiguration() {
		return $this->publishingConfiguration;
	}

}
?>

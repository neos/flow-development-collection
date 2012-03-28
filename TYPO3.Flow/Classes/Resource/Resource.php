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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Model describing a resource
 *
 * @FLOW3\Entity
 */
class Resource {

	/**
	 * @var \TYPO3\FLOW3\Resource\ResourcePointer
	 * @ORM\ManyToOne
	 * @FLOW3\Identity
	 */
	protected $resourcePointer;

	/**
	 * @var \TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface
	 */
	protected $publishingConfiguration;

	/**
	 * @var string
	 * @FLOW3\Validate(type="StringLength", options={ "maximum"=100 })
	 * @FLOW3\Identity
	 */
	protected $filename = '';

	/**
	 * @var string
	 * @FLOW3\Validate(type="StringLength", options={ "maximum"=100 })
	 * @FLOW3\Identity
	 */
	protected $fileExtension = '';

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
	 */
	public function getMimeType() {
		return \TYPO3\FLOW3\Utility\FileTypes::getMimeTypeFromFilename('x.' . $this->getFileExtension());
	}

	/**
	 * Sets the resource pointer
	 *
	 * @param \TYPO3\FLOW3\Resource\ResourcePointer $resourcePointer
	 * @return void
	 */
	public function setResourcePointer(\TYPO3\FLOW3\Resource\ResourcePointer $resourcePointer) {
		$this->resourcePointer = $resourcePointer;
	}

	/**
	 * Returns the resource pointer
	 *
	 * @return \TYPO3\FLOW3\Resource\ResourcePointer $resourcePointer
	 */
	public function getResourcePointer() {
		return $this->resourcePointer;
	}

	/**
	 * Sets the publishing configuration for this resource
	 *
	 * @param \TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface $publishingConfiguration The publishing configuration
	 * @return void
	 */
	public function setPublishingConfiguration(\TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface $publishingConfiguration = NULL) {
		$this->publishingConfiguration = $publishingConfiguration;
	}

	/**
	 * Returns the publishing configuration for this resource
	 *
	 * @return \TYPO3\FLOW3\Resource\Publishing\PublishingConfigurationInterface The publishing configuration
	 */
	public function getPublishingConfiguration() {
		return $this->publishingConfiguration;
	}

}
?>

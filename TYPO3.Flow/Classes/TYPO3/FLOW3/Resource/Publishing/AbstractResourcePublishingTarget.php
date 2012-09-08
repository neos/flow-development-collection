<?php
namespace TYPO3\FLOW3\Resource\Publishing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Resource publishing targets provide methods to publish resources to a certain
 * channel, such as the local file system or a content delivery network.
 *
 */
abstract class AbstractResourcePublishingTarget implements \TYPO3\FLOW3\Resource\Publishing\ResourcePublishingTargetInterface {

	/**
	 * Rewrites the given resource filename to a human readable but still URI compatible string.
	 *
	 * @param string $filename The raw resource filename
	 * @return string The rewritten title
	 */
	protected function rewriteFilenameForUri($filename) {
		return preg_replace(array('/ /', '/_/', '/[^-a-z0-9.]/i'), array('-', '-', ''), $filename);
	}

	/**
	 * Returns the private path to the source of the given resource.
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource
	 * @return mixed The full path and filename to the source of the given resource or FALSE if the resource file doesn't exist
	 */
	protected function getPersistentResourceSourcePathAndFilename(\TYPO3\FLOW3\Resource\Resource $resource) {
		$pathAndFilename = FLOW3_PATH_DATA . 'Persistent/Resources/' . $resource->getResourcePointer()->getHash();
		return (file_exists($pathAndFilename)) ? $pathAndFilename : FALSE;
	}
}

?>
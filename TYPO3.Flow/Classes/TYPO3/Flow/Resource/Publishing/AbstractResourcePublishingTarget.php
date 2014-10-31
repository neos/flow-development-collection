<?php
namespace TYPO3\Flow\Resource\Publishing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Resource\Resource;

/**
 * Resource publishing targets provide methods to publish resources to a certain
 * channel, such as the local file system or a content delivery network.
 *
 */
abstract class AbstractResourcePublishingTarget implements ResourcePublishingTargetInterface {

	/**
	 * Rewrites the given resource filename to a human readable but still URI compatible string.
	 *
	 * @param string $filename The raw resource filename
	 * @return string The rewritten title
	 */
	protected function rewriteFilenameForUri($filename) {
		$filename = preg_replace(array('/ /', '/_/', '/[^-\w0-9.]/iu'), array('-', '-', ''), $filename);
		$pathInfo = pathinfo($filename);
		if ($pathInfo['filename'] === '') {
			$filename = 'unnamed';
			if (isset($pathInfo['extension'])) {
				$filename .= '.' . $pathInfo['extension'];
			}
		}
		return $filename;
	}

	/**
	 * Returns the private path to the source of the given resource.
	 *
	 * @param Resource $resource
	 * @return mixed The full path and filename to the source of the given resource or FALSE if the resource file doesn't exist
	 */
	protected function getPersistentResourceSourcePathAndFilename(Resource $resource) {
		$pathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resource->getResourcePointer()->getHash();
		return (file_exists($pathAndFilename)) ? $pathAndFilename : FALSE;
	}
}

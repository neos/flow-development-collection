<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource\Publishing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Resource publishing targets provide methods to publish resources to a certain
 * channel, such as the local file system or a content delivery network.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractResourcePublishingTarget implements \F3\FLOW3\Resource\Publishing\ResourcePublishingTargetInterface {

	/**
	 * Rewrites the given resource title to a human readable but still URI compatible string.
	 *
	 * @param string $title The raw resource title
	 * @return string The rewritten title
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function rewriteTitleForUri($title) {
		return preg_replace(array('/ /', '/_/', '/[^-a-z0-9]/i'), array('-', '-', ''), $title);
	}

	/**
	 * Returns the private path to the source of the given resource.
	 *
	 * @param \F3\FLOW3\Resource\Resource $resource
	 * @return mixed The full path and filename to the source of the given resource or FALSE if the resource file doesn't exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getPersistentResourceSourcePathAndFilename(\F3\FLOW3\Resource\Resource $resource) {
		$pathAndFilename = FLOW3_PATH_DATA . 'Persistent/Resources/' . $resource->getHash();
		return (file_exists($pathAndFilename)) ? $pathAndFilename : FALSE;
	}
}

?>
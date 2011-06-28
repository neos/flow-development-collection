<?php
namespace TYPO3\FLOW3\Tests\Functional\Resource\Publishing;

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
 * Stub filesystem publishing target, hardcoding the Resource base URI to an arbitary,
 * fixed URI.
 *
 * In Objects.yaml for testing it is configured that this class is taken instead
 * of the normal FileSystemPublishingTarget.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TestingFileSystemPublishingTarget extends \TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget {

	/**
	 * Always returns a fixed base URI of http://baseuri/_Resources/
	 *
	 * @return string the base URI
	 */
	protected function detectResourcesBaseUri() {
		$this->resourcesBaseUri = 'http://baseuri/_Resources/';
	}
}
?>
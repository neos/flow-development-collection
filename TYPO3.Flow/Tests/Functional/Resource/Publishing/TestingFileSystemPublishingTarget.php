<?php
namespace TYPO3\FLOW3\Tests\Functional\Resource\Publishing;

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
 * Stub filesystem publishing target, hardcoding the Resource base URI to an arbitary,
 * fixed URI.
 *
 * In Objects.yaml for testing it is configured that this class is taken instead
 * of the normal FileSystemPublishingTarget.
 *
 */
class TestingFileSystemPublishingTarget extends \TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget {

	/**
	 * Always returns a fixed base URI of http://baseuri/_Resources/
	 *
	 * @return void
	 */
	protected function detectResourcesBaseUri() {
		$this->resourcesBaseUri = 'http://baseuri/_Resources/';
	}
}
?>
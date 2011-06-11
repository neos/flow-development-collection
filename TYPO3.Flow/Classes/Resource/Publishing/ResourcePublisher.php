<?php
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
 * Support functions for handling assets
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class ResourcePublisher {

	/**
	 * @var \F3\FLOW3\Resource\Publishing\ResourcePublishingTargetInterface
	 */
	protected $resourcePublishingTarget;

	/**
	 * Injects the resource publishing target
	 * 
	 * @param \F3\FLOW3\Resource\Publishing\ResourcePublishingTargetInterface $resourcePublishingTarget
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectResourcePublishingTarget(\F3\FLOW3\Resource\Publishing\ResourcePublishingTargetInterface $resourcePublishingTarget) {
		$this->resourcePublishingTarget = $resourcePublishingTarget;
	}

	/**
	 * Recursively publishes all resources found in the specified source directory
	 * to the given destination.
	 *
	 * @param string $sourcePath Path containing the resources to publish
	 * @param string $relativeTargetPath Path relative to the public resources directory where the given resources are mirrored to
	 * @return boolean TRUE if publication succeeded or FALSE if the resources could not be published
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishStaticResources($sourcePath, $relativeTargetPath) {
		return $this->resourcePublishingTarget->publishStaticResources($sourcePath, $relativeTargetPath);
	}

	/**
	 * Publishes a persistent resource
	 *
	 * @param \F3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishPersistentResource(\F3\FLOW3\Resource\Resource $resource) {
		return $this->resourcePublishingTarget->publishPersistentResource($resource);
	}

	/**
	 * Unpublishes a persistent resource
	 *
	 * @param \F3\FLOW3\Resource\Resource $resource The resource to unpublish
	 * @return boolean TRUE if at least one file was removed, FALSE otherwise
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unpublishPersistentResource(\F3\FLOW3\Resource\Resource $resource) {
		return $this->resourcePublishingTarget->unpublishPersistentResource($resource);
	}

	/**
	 * Returns the base URI pointing to the published static resources
	 *
	 * @return string The base URI pointing to web accessible static resources
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStaticResourcesWebBaseUri() {
		return $this->resourcePublishingTarget->getStaticResourcesWebBaseUri();
	}

	/**
	 * Returns the URI pointing to the published persistent resource
	 *
	 * @param \F3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPersistentResourceWebUri($resource) {
		return $this->resourcePublishingTarget->getPersistentResourceWebUri($resource);
	}
}

?>
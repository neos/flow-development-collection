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
interface ResourcePublishingTargetInterface {

	/**
	 * Recursively publishes static resources located in the specified directory.
	 * These resources are typically public package resources provided by the active packages.
	 *
	 * @param string $sourcePath The full path to the source directory which should be published (includes sub directories)
	 * @param string $relativeTargetPath Path relative to the target's root where resources should be published to.
	 * @return boolean TRUE if publication succeeded or FALSE if the resources could not be published
	 */
	public function publishStaticResources($sourcePath, $relativeTargetPath);

	/**
	 * Returns the base URI pointing to the published static resources
	 *
	 * @return string The base URI pointing to web accessible static resources
	 */
	public function getStaticResourcesWebBaseUri();

	/**
	 * Publishes a persistent resource.
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function publishPersistentResource(\TYPO3\FLOW3\Resource\Resource $resource);

	/**
	 * Unpublishes a persistent resource.
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return boolean TRUE if at least one file was removed, FALSE otherwise
	 */
	public function unpublishPersistentResource(\TYPO3\FLOW3\Resource\Resource $resource);

	/**
	 * Returns the URI pointing to the published persistent resource
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function getPersistentResourceWebUri(\TYPO3\FLOW3\Resource\Resource $resource);
}

?>
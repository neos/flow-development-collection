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
 * Publishing target for a file system.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
class FileSystemPublishingTarget extends \F3\FLOW3\Resource\Publishing\AbstractResourcePublishingTarget {

	/**
	 * @var string
	 */
	protected $resourcesPublishingPath;

	/**
	 * @var \F3\FLOW3\Property\DataType\Uri
	 */
	protected $resourcesBaseUri;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Injects the server environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment The environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the settings of this package
	 * 
	 * @param array $settings 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes this publishing target
	 * 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		if ($this->resourcesPublishingPath === NULL) {
			$this->resourcesPublishingPath = FLOW3_PATH_WEB . '_Resources/';
		}

		if (!is_writable($this->resourcesPublishingPath)) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($this->resourcesPublishingPath);
		}
		if (!is_dir($this->resourcesPublishingPath)) {
			throw new \F3\FLOW3\Resource\Exception('The directory "' . $this->resourcesPublishingPath . '" does not exist.', 1207124538);
		}
		if (!is_writable($this->resourcesPublishingPath)) {
			throw new \F3\FLOW3\Resource\Exception('The directory "' . $this->resourcesPublishingPath . '" is not writable.', 1207124546);
		}
		if (!is_dir($this->resourcesPublishingPath . 'Persistent')) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($this->resourcesPublishingPath . 'Persistent');
		}
		if (!is_writable($this->resourcesPublishingPath . 'Persistent')) {
			throw new \F3\FLOW3\Resource\Exception('The directory "' . $this->resourcesPublishingPath . 'Persistent" is not writable.', 1260527881);
		}

		$this->detectResourcesBaseUri();
	}

	/**
	 * Recursively publishes static resources located in the specified directory.
	 * These resources are typically public package resources provided by the active packages.
	 *
	 * @param string $sourcePath The full path to the source directory which should be published (includes sub directories)
	 * @param string $relativeTargetPath Path relative to the target's root where resources should be published to.
	 * @return boolean TRUE if publication succeeded or FALSE if the resources could not be published
	 */
	public function publishStaticResources($sourcePath, $relativeTargetPath) {
		if (!is_dir($sourcePath)) {
			return FALSE;
		}

		$targetPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->resourcesPublishingPath . 'Static/', $relativeTargetPath));

		foreach (\F3\FLOW3\Utility\Files::readDirectoryRecursively($sourcePath) as $sourcePathAndFilename) {
			if (substr(strtolower($sourcePathAndFilename), -4, 4) === '.php') continue;
			$targetPathAndFilename = \F3\FLOW3\Utility\Files::concatenatePaths(array($targetPath, str_replace($sourcePath, '', $sourcePathAndFilename)));
			if (!file_exists($targetPathAndFilename) || filemtime($sourcePathAndFilename) > filemtime($targetPathAndFilename)) {
				$this->mirrorFile($sourcePathAndFilename, $targetPathAndFilename, TRUE);
			}
		}
		
		return TRUE;
	}

	/**
	 * Publishes a persistent resource to the web accessible resources directory.
	 *
	 * @param \F3\FLOW3\Resource\Resource $resource The resource to publish
	 * @param string $title An optional title which is used in the public URI pointing to the published resource
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishPersistentResource(\F3\FLOW3\Resource\Resource $resource, $title = '') {
		$rewrittenTitle = ($title === '') ? '' : '/' . $this->rewriteTitleForUri($title);

		$publishedResourcePathAndFilename = $this->resourcesPublishingPath . 'Persistent/' . $resource->getHash() . '.' . $resource->getFileExtension();
		$publishedResourceWebUri = $this->resourcesBaseUri . 'Persistent/' . $resource->getHash() . $rewrittenTitle . '.' . $resource->getFileExtension();

		if (!file_exists($publishedResourcePathAndFilename)) {
			$unpublishedResourcePathAndFilename = $this->getPersistentResourceSourcePathAndFilename($resource);
			if ($unpublishedResourcePathAndFilename === FALSE) {
				return FALSE;
			}
			$this->mirrorFile($unpublishedResourcePathAndFilename, $publishedResourcePathAndFilename, FALSE);
		}
		return $publishedResourceWebUri;
	}

	/**
	 * Returns the base URI pointing to the published static resources
	 *
	 * @return string The base URI pointing to web accessible static resources
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStaticResourcesWebBaseUri() {
		return $this->resourcesBaseUri . 'Static/';
	}

	/**
	 * Returns the web URI pointing to the published persistent resource
	 *
	 * @param \F3\FLOW3\Resource\Resource $resource The resource to publish
	 * @param string $title An optional title which is used in the public URI pointing to the published resource
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPersistentResourceWebUri(\F3\FLOW3\Resource\Resource $resource, $title = '') {
		return $this->publishPersistentResource($resource, $title);
	}

	/**
	 * Detects the (resources) base URI and stores it as a protected
	 * class variable.
	 *
	 * This functionality somewhat duplicates the detection used in the Web
	 * Request Builder but for the time being this should be good enough.
	 *
	 * $this->resourcesPublishingPath must be set prior to calling this method.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function detectResourcesBaseUri() {
		$uri = $this->environment->getRequestUri();
		$uri->setQuery(NULL);
		$uri->setFragment(NULL);
		$requestPathSegments = explode('/', $this->environment->getScriptRequestPathAndName());
		array_pop($requestPathSegments);
		$uri->setPath(implode('/', $requestPathSegments) . '/');

		$this->resourcesBaseUri = $uri . substr($this->resourcesPublishingPath, strlen(FLOW3_PATH_WEB));
	}

	/**
	 * Depending on the settings of this publishing target copies the specified file
	 * or creates a symbolic link.
	 *
	 * @param string $sourcePathAndFilename
	 * @param string $targetPathAndFilename
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function mirrorFile($sourcePathAndFilename, $targetPathAndFilename, $createDirectoriesIfNecessary) {
		if ($createDirectoriesIfNecessary === TRUE) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		switch ($this->settings['resource']['publishing']['fileSystem']['mirrorMode']) {
			case 'copy' :
				copy($sourcePathAndFilename, $targetPathAndFilename);
				touch($targetPathAndFilename, filemtime($sourcePathAndFilename));
				break;
			case 'link' :
				if (file_exists($targetPathAndFilename)) {
					if (is_link($targetPathAndFilename) && (readlink($targetPathAndFilename) === $sourcePathAndFilename)) {
						break;
					}
					unlink($targetPathAndFilename);
					symlink($sourcePathAndFilename, $targetPathAndFilename);
				} else {
					symlink($sourcePathAndFilename, $targetPathAndFilename);
				}
				break;
			default :
				throw new \F3\FLOW3\Resource\Exception('An invalid mirror mode (' . $this->settings['resource']['publishing']['fileSystem']['mirrorMode'] . ') has been configured.', 1256133400);
		}

		if (!file_exists($targetPathAndFilename)) {
			throw new \F3\FLOW3\Resource\Exception('The resource "' . str_replace($sourcePath, '', $sourcePathAndFilename) . '" could not be mirrored.', 1207255453);
		}
	}
}

?>
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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Publishing target for a file system.
 *
 * @FLOW3\Scope("singleton")
 */
class FileSystemPublishingTarget extends \TYPO3\FLOW3\Resource\Publishing\AbstractResourcePublishingTarget {

	/**
	 * @var string
	 */
	protected $resourcesPublishingPath;

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $resourcesBaseUri;

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Injects the bootstrap
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function injectBootstrap(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes this publishing target
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Resource\Exception
	 */
	public function initializeObject() {
		if ($this->resourcesPublishingPath === NULL) {
			$this->resourcesPublishingPath = FLOW3_PATH_WEB . '_Resources/';
		}

		if (!is_writable($this->resourcesPublishingPath)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->resourcesPublishingPath);
		}
		if (!is_dir($this->resourcesPublishingPath) && !is_link($this->resourcesPublishingPath)) {
			throw new \TYPO3\FLOW3\Resource\Exception('The directory "' . $this->resourcesPublishingPath . '" does not exist.', 1207124538);
		}
		if (!is_writable($this->resourcesPublishingPath)) {
			throw new \TYPO3\FLOW3\Resource\Exception('The directory "' . $this->resourcesPublishingPath . '" is not writable.', 1207124546);
		}
		if (!is_dir($this->resourcesPublishingPath . 'Persistent') && !is_link($this->resourcesPublishingPath . 'Persistent')) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->resourcesPublishingPath . 'Persistent');
		}
		if (!is_writable($this->resourcesPublishingPath . 'Persistent')) {
			throw new \TYPO3\FLOW3\Resource\Exception('The directory "' . $this->resourcesPublishingPath . 'Persistent" is not writable.', 1260527881);
		}
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
		$sourcePath = rtrim(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($this->realpath($sourcePath)), '/');
		$targetPath = rtrim(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->resourcesPublishingPath, 'Static', $relativeTargetPath)), '/');

		if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {
			if (\TYPO3\FLOW3\Utility\Files::is_link($targetPath) && (rtrim(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($this->realpath($targetPath)), '/') === $sourcePath)) {
				return TRUE;
			} elseif (is_dir($targetPath)) {
				\TYPO3\FLOW3\Utility\Files::removeDirectoryRecursively($targetPath);
			} elseif (is_link($targetPath)) {
				unlink($targetPath);
			} else {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($targetPath));
			}
			symlink($sourcePath, $targetPath);
		} else {
			foreach (\TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($sourcePath) as $sourcePathAndFilename) {
				if (substr(strtolower($sourcePathAndFilename), -4, 4) === '.php') continue;
				$targetPathAndFilename = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($targetPath, str_replace($sourcePath, '', $sourcePathAndFilename)));
				if (!file_exists($targetPathAndFilename) || filemtime($sourcePathAndFilename) > filemtime($targetPathAndFilename)) {
					$this->mirrorFile($sourcePathAndFilename, $targetPathAndFilename, TRUE);
				}
			}
		}

		return TRUE;
	}

	/**
	 * Publishes a persistent resource to the web accessible resources directory.
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function publishPersistentResource(\TYPO3\FLOW3\Resource\Resource $resource) {
		$publishedResourcePathAndFilename = $this->buildPersistentResourcePublishPathAndFilename($resource, TRUE);
		$publishedResourceWebUri = $this->buildPersistentResourceWebUri($resource);

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
	 * Unpublishes a persistent resource in the web accessible resources directory.
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to unpublish
	 * @return boolean TRUE if at least one file was removed, FALSE otherwise
	 */
	public function unpublishPersistentResource(\TYPO3\FLOW3\Resource\Resource $resource) {
		$result = FALSE;
		foreach (glob($this->buildPersistentResourcePublishPathAndFilename($resource, FALSE) . '*') as $publishedResourcePathAndFilename) {
			unlink($publishedResourcePathAndFilename);
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Returns the base URI where persistent resources are published an accessible from the outside.
	 *
	 * @return \TYPO3\FLOW3\Http\Uri The base URI
	 */
	public function getResourcesBaseUri() {
		if ($this->resourcesBaseUri === NULL) {
			$this->detectResourcesBaseUri();
		}
		return $this->resourcesBaseUri;
	}

	/**
	 * Returns the publishing path where resources are published in the local filesystem
	 * @return string The resources publishing path
	 */
	public function getResourcesPublishingPath() {
		return $this->resourcesPublishingPath;
	}

	/**
	 * Returns the base URI pointing to the published static resources
	 *
	 * @return string The base URI pointing to web accessible static resources
	 */
	public function getStaticResourcesWebBaseUri() {
		return $this->getResourcesBaseUri() . 'Static/';
	}

	/**
	 * Returns the web URI pointing to the published persistent resource
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function getPersistentResourceWebUri(\TYPO3\FLOW3\Resource\Resource $resource) {
		return $this->publishPersistentResource($resource);
	}

	/**
	 * Detects the (resources) base URI and stores it as a protected class variable.
	 *
	 * $this->resourcesPublishingPath must be set prior to calling this method.
	 *
	 * @return void
	 */
	protected function detectResourcesBaseUri() {
		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof \TYPO3\FLOW3\Http\HttpRequestHandlerInterface) {
			$uri = $requestHandler->getHttpRequest()->getBaseUri();
		} else {
			$uri = '';
		}
		$this->resourcesBaseUri = $uri . substr($this->resourcesPublishingPath, strlen(FLOW3_PATH_WEB));
	}

	/**
	 * Depending on the settings of this publishing target copies the specified file
	 * or creates a symbolic link.
	 *
	 * @param string $sourcePathAndFilename
	 * @param string $targetPathAndFilename
	 * @param boolean $createDirectoriesIfNecessary
	 * @return void
	 * @throws \TYPO3\FLOW3\Resource\Exception
	 */
	protected function mirrorFile($sourcePathAndFilename, $targetPathAndFilename, $createDirectoriesIfNecessary = FALSE) {
		if ($createDirectoriesIfNecessary === TRUE) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		switch ($this->settings['resource']['publishing']['fileSystem']['mirrorMode']) {
			case 'copy' :
				copy($sourcePathAndFilename, $targetPathAndFilename);
				touch($targetPathAndFilename, filemtime($sourcePathAndFilename));
				break;
			case 'link' :
				if (file_exists($targetPathAndFilename)) {
					if (\TYPO3\FLOW3\Utility\Files::is_link($targetPathAndFilename) && ($this->realpath($targetPathAndFilename) === $this->realpath($sourcePathAndFilename))) {
						break;
					}
					unlink($targetPathAndFilename);
					symlink($sourcePathAndFilename, $targetPathAndFilename);
				} else {
					symlink($sourcePathAndFilename, $targetPathAndFilename);
				}
				break;
			default :
				throw new \TYPO3\FLOW3\Resource\Exception('An invalid mirror mode (' . $this->settings['resource']['publishing']['fileSystem']['mirrorMode'] . ') has been configured.', 1256133400);
		}

		if (!file_exists($targetPathAndFilename)) {
			throw new \TYPO3\FLOW3\Resource\Exception('The resource "' . $sourcePathAndFilename . '" could not be mirrored.', 1207255453);
		}
	}

	/**
	 * Returns the web URI to be used to publish the specified persistent resource
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to build the URI for
	 * @return string The web URI
	 */
	protected function buildPersistentResourceWebUri(\TYPO3\FLOW3\Resource\Resource $resource) {
		$filename = $resource->getFilename();
		$rewrittenFilename = ($filename === '' || $filename === NULL) ? '' : '/' . $this->rewriteFilenameForUri($filename);
		return $this->getResourcesBaseUri() . 'Persistent/' . $resource->getResourcePointer()->getHash() . $rewrittenFilename;
	}

	/**
	 * Returns the publish path and filename to be used to publish the specified persistent resource
	 *
	 * @param \TYPO3\FLOW3\Resource\Resource $resource The resource to build the publish path and filename for
	 * @param boolean $returnFilename FALSE if only the directory without the filename should be returned
	 * @return string The publish path and filename
	 */
	protected function buildPersistentResourcePublishPathAndFilename(\TYPO3\FLOW3\Resource\Resource $resource, $returnFilename) {
		$publishPath = $this->resourcesPublishingPath . 'Persistent/';
		if ($returnFilename === TRUE) return $publishPath . $resource->getResourcePointer()->getHash() . '.' . $resource->getFileExtension();
		return $publishPath;
	}

	/**
	 * Wrapper around realpath(). Needed for testing, as realpath() cannot be mocked
	 * by vfsStream.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function realpath($path) {
		return realpath($path);
	}
}

?>
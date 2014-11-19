<?php
namespace TYPO3\Flow\Resource\Storage;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Files;

/**
 * A resource storage which stores and retrieves resources from active Flow packages.
 */
class PackageStorage extends FileSystemStorage {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Initializes this resource storage
	 *
	 * @return void
	 */
	public function initializeObject() {
		// override the parent method because we don't need that here
	}

	/**
	 * Retrieve all Objects stored in this storage.
	 *
	 * @return array<\TYPO3\Flow\Resource\Storage\Object>
	 */
	public function getObjects() {
		return $this->getObjectsByPathPattern('*');
	}

	/**
	 * Return all Objects stored in this storage filtered by the given directory / filename pattern
	 *
	 * @param string $pattern A glob compatible directory / filename pattern
	 * @return array<\TYPO3\Flow\Resource\Storage\Object>
	 */
	public function getObjectsByPathPattern($pattern) {
		$objects = array();
		$directories = array();

		if (strpos($pattern, '/') !== FALSE) {
			list($packageKeyPattern, $directoryPattern) = explode('/', $pattern, 2);
		} else {
			$packageKeyPattern = $pattern;
			$directoryPattern = '*';
		}
		// $packageKeyPattern can be used in a future implementation to filter by package key

		$packages = $this->packageManager->getActivePackages();
		foreach ($packages as $packageKey => $package) {
			/** @var PackageInterface $package */
			if ($directoryPattern === '*') {
				$directories[$packageKey][] = $package->getPackagePath();
			} else {
				$directories[$packageKey] = glob($package->getPackagePath() . $directoryPattern, GLOB_ONLYDIR);
			}
		}

		foreach ($directories as $packageKey => $packageDirectories) {
			foreach ($packageDirectories as $directoryPath) {
				foreach (Files::readDirectoryRecursively($directoryPath) as $resourcePathAndFilename) {
					$pathInfo = pathinfo($resourcePathAndFilename);

					$object = new Object();
					$object->setFilename($pathInfo['basename']);
					$object->setSha1(sha1_file($resourcePathAndFilename));
					$object->setMd5(md5_file($resourcePathAndFilename));
					$object->setFileSize(filesize($resourcePathAndFilename));
					if (isset($pathInfo['dirname'])) {
						list(, $path) = explode('/', str_replace($packages[$packageKey]->getResourcesPath(), '', $pathInfo['dirname']), 2);
						$object->setRelativePublicationPath($packageKey . '/' . $path . '/');
					}
					$object->setStream(function() use ($resourcePathAndFilename) { return fopen($resourcePathAndFilename, 'r'); });
					$objects[] = $object;
				}
			}
		}

		return $objects;
	}

	/**
	 * Because we cannot store persistent resources in a PackageStorage, this method always returns FALSE.
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource stored in this storage
	 * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
	 */
	public function getStreamByResource(Resource $resource) {
		return FALSE;
	}

	/**
	 * Returns the absolute paths of public resources directories of all active packages.
	 * This method is used directly by the FileSystemSymlinkTarget.
	 *
	 * @return array<string>
	 */
	public function getPublicResourcePaths() {
		$paths = array();
		$packages = $this->packageManager->getActivePackages();
		foreach ($packages as $packageKey => $package) {
			/** @var PackageInterface $package */
			$publicResourcesPath = Files::concatenatePaths(array($package->getResourcesPath(), 'Public'));
			if (is_dir($publicResourcesPath)) {
				$paths[$packageKey] = $publicResourcesPath;
			}
		}
		return $paths;
	}
}


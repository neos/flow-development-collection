<?php
namespace TYPO3\FLOW3\Package;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Utility\Files;

/**
 * Class for building Packages
 */
class PackageFactory {

	/**
	 * Returns a package instance.
	 *
	 * @param string $packagesBasePath the base install path of packages,
	 * @param string $manifestPath path to the package's Composer manifest, relative to base path
	 * @param string $packageKey key / name of the package
	 * @param string $classesPath path to the classes directory, relative to the package path
	 * @return \TYPO3\FLOW3\Package\PackageInterface
	 * @throws Exception\CorruptPackageException
	 */
	public static function create($packagesBasePath, $manifestPath, $packageKey, $classesPath) {
		$packageClassPathAndFilename = Files::concatenatePaths(array($packagesBasePath, $manifestPath, 'Classes/' . str_replace('.', '/', $packageKey) . '/Package.php'));
		if (file_exists($packageClassPathAndFilename)) {
			require_once($packageClassPathAndFilename);
			/**
			 * @todo there should be a general method for getting Namespace from $packageKey
			 * @todo it should be tested if the package class implements the interface
			 */
			$packageClassName = str_replace('.', '\\', $packageKey) . '\Package';
			if (!class_exists($packageClassName)) {
				throw new \TYPO3\FLOW3\Package\Exception\CorruptPackageException(sprintf('The package "%s" does not contain a valid package class. Check if the file "%s" really contains a class called "%s".', $packageKey, $packageClassPathAndFilename, $packageClassName), 1327587091);
			}
		} else {
			$packageClassName = 'TYPO3\FLOW3\Package\Package';
		}
		$packagePath = Files::concatenatePaths(array($packagesBasePath, $manifestPath)) . '/';

		$package = new $packageClassName($packageKey, $packagePath, $classesPath);
		return $package;
	}

	/**
	 * Resolves package key from Composer manifest
	 *
	 * If it is a FLOW3 package the name of the containing directory will be used.
	 *
	 * Else if the composer name of the package matches the first part of the lowercased namespace of the package, the mixed
	 * case version of the composer name / namespace will be used, with backslashes replaced by dots.
	 *
	 * Else the composer name will be used with the slash replaced by a dot
	 *
	 * @param string $manifestPath
	 * @param string $packagesBasePath
	 * @return string
	 */
	public static function getPackageKeyFromManifestPath($manifestPath, $packagesBasePath) {
		if (!file_exists($manifestPath . '/composer.json')) {
			throw new  \TYPO3\FLOW3\Package\Exception\MissingPackageManifestException('No "composer.json" found in ' . $manifestPath, 1348146557);
		}
		$composerJson = Files::getFileContents($manifestPath . '/composer.json');
		$manifest = json_decode($composerJson);
		if (!is_object($manifest)) {
			throw new  \TYPO3\FLOW3\Package\Exception\InvalidPackageManifestException('Invalid composer manifest in ' . $manifestPath, 1348146450);
		}
		if (substr($manifest->type, 0, 6) === 'flow3-') {
			$relativePackagePath = substr($manifestPath, strlen($packagesBasePath));
			$packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
			/**
			 * @todo check that manifest name and directory follows convention
			 */
		} else {
			$packageKey = str_replace('/', '.', $manifest->name);
			if (isset($manifest->autoload) && isset($manifest->autoload->{"psr-0"})) {
				$namespaces = array_keys(get_object_vars($manifest->autoload->{"psr-0"}));
				foreach ($namespaces as $namespace) {
					$namespaceLead = substr($namespace, 0, strlen($manifest->name));
					$dottedNamespaceLead = str_replace('\\', '.', $namespaceLead);
					if (strtolower($dottedNamespaceLead) === $packageKey) {
						$packageKey = $dottedNamespaceLead;
					}
				}
			}
		}
		$packageKey = preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);
		return $packageKey;
	}
}
?>
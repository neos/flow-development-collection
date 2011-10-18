<?php
namespace TYPO3\FLOW3\Core;

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
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @FLOW3\Proxy(false)
 * @FLOW3\Scope("singleton")
 */
class ClassLoader {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * An array of \TYPO3\FLOW3\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * Injects the cache for storing the renamed original classes
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 */
	public function injectClassesCache(\TYPO3\FLOW3\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return void
	 */
	public function loadClass($className) {
		if ($this->classesCache !== NULL) {
			$this->classesCache->requireOnce(str_replace('\\', '_', $className));
			if (class_exists($className, FALSE)) {
				return TRUE;
			}
		}

		foreach ($this->packages as $packageKey => $package) {
			$packageNamespace = str_replace('.', '\\', $packageKey);
			$packageNamespaceLength = strlen($packageNamespace);
			if (substr($className, 0, $packageNamespaceLength) === $packageNamespace && $className[$packageNamespaceLength] === '\\') {
				if (substr($className, $packageNamespaceLength + 1, 16) === 'Tests\Functional') {
					$classFilePathAndName = $this->packages[$packageKey]->getPackagePath();
				} else {
					$classFilePathAndName = $this->packages[$packageKey]->getClassesPath();
				}
				$classFilePathAndName .= str_replace('\\', '/', substr($className, $packageNamespaceLength + 1)) . '.php';
				break;
			}
		}

		if ($this->packages === array() && substr($className, 0, 11) === 'TYPO3\FLOW3') {
			$classFilePathAndName = FLOW3_PATH_FLOW3 . 'Classes/' . str_replace('\\', '/', substr($className, 12)) . '.php';
		}

		if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
			require($classFilePathAndName);
			return TRUE;
		}
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \TYPO3\FLOW3\Package\Package objects
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

}

?>
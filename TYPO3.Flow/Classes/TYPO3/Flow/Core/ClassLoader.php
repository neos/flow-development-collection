<?php
namespace TYPO3\Flow\Core;

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

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ClassLoader {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * The path where composer packages are installed by default, usually Packages/Library
	 *
	 * @var string
	 */
	protected $defaultVendorDirectory;

	/**
	 * A list of namespaces this class loader is definitely responsible for.
	 *
	 * @var array
	 */
	protected $packageNamespaces = array();

	/**
	 * @var boolean
	 */
	protected $considerTestsNamespace = FALSE;

	/**
	 * @var array
	 */
	protected $ignoredClassNames = array(
		'integer' => TRUE,
		'string' => TRUE,
		'param' => TRUE,
		'return' => TRUE,
		'var' => TRUE,
		'throws' => TRUE,
		'api' => TRUE,
		'todo' => TRUE,
		'fixme' => TRUE,
		'see' => TRUE,
		'license' => TRUE,
		'author' => TRUE,
		'test' => TRUE,
		'deprecated' => TRUE,
		'internal' => TRUE,
		'since' => TRUE,
	);

	/**
	 * Map of FQ classname to include path.
	 *
	 * @var array
	 */
	protected $classMap;

	/**
	 * @var array
	 */
	protected $fallbackClassPaths = array();

	/**
	 * Cache classNames that were not found in this class loader in order
	 * to save time in resolving those non existent classes.
	 * Usually these will be annotations that have no class.
	 *
	 * @var array
	 */
	protected $nonExistentClasses = array();

	/**
	 *
	 */
	public function __construct() {
		$distributionComposerManifest = json_decode(file_get_contents(FLOW_PATH_ROOT . 'composer.json'));
		$this->defaultVendorDirectory = $distributionComposerManifest->config->{"vendor-dir"};
		$composerPath = FLOW_PATH_ROOT . $this->defaultVendorDirectory . '/composer/';
		$this->initializeComposerAutoloadInformation($composerPath);
	}

	/**
	 * Injects the cache for storing the renamed original classes
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 */
	public function injectClassesCache(\TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return boolean
	 */
	public function loadClass($className) {
		if ($className[0] === '\\') {
			$className = ltrim($className, '\\');
		}

		$namespaceParts = explode('\\', $className);

		// Workaround for Doctrine's annotation parser which does a class_exists() for annotations like "@param" and so on:
		if (isset($this->ignoredClassNames[$className]) || isset($this->ignoredClassNames[end($namespaceParts)]) || isset($this->nonExistentClasses[$className])) {
			return FALSE;
		}

		// Loads any known proxied class:
		if ($this->classesCache !== NULL && $this->classesCache->requireOnce(implode('_', $namespaceParts)) !== FALSE) {
			return TRUE;
		}

		if (isset($this->classMap[$className])) {
			include($this->classMap[$className]);
			return TRUE;
		}

		$classNamePart = array_pop($namespaceParts);
		$classNameParts = explode('_', $classNamePart);
		$namespaceParts = array_merge($namespaceParts, $classNameParts);
		$namespacePartCount = count($namespaceParts);

		// Load classes from the Flow package at a very early stage where
		// no packages have been registered yet:
		if ($this->packageNamespaces === array()) {
			if ($namespaceParts[0] === 'TYPO3' && $namespaceParts[1] === 'Flow') {
				require(FLOW_PATH_FLOW . 'Classes/TYPO3/Flow/' . implode('/', array_slice($namespaceParts, 2)) . '.php');
				return TRUE;
			} else {
				return FALSE;
			}
		}

		$currentPackageArray = $this->packageNamespaces;
		$packagenamespacePartCount = 0;
		while (($packagenamespacePartCount + 1) < $namespacePartCount) {
			$possiblePackageNamespacePart = $namespaceParts[$packagenamespacePartCount];
			if (isset($currentPackageArray[$possiblePackageNamespacePart])) {
				$packagenamespacePartCount++;
				$currentPackageArray = $currentPackageArray[$possiblePackageNamespacePart];
			} else {
				break;
			}
		}

		if (isset($currentPackageArray['_pathData'])) {
			$possiblePaths = $currentPackageArray['_pathData'];
		} else {
			$packagenamespacePartCount = 0;
			$possiblePaths = $this->fallbackClassPaths;
		}


		foreach ($possiblePaths as $possiblePathData) {
			$pathConstructor = 'buildClassPathWith' . $possiblePathData['mappingType'];
			$possibleFilePath = $this->$pathConstructor($namespaceParts, $possiblePathData['path'], $packagenamespacePartCount);
			if (file_exists($possibleFilePath)) {
				$result = include($possibleFilePath);
				if ($result !== FALSE) {
					return TRUE;
				}
			}
		}

		$this->nonExistentClasses[$className] = TRUE;
		return FALSE;
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \TYPO3\Flow\Package\Package objects
	 * @return void
	 */
	public function setPackages(array $packages) {
		foreach ($packages as $package) {
			$this->createNamespaceMapEntry($package->getNamespace(), $package->getClassesPath());
			if ($this->considerTestsNamespace) {
				$this->createNamespaceMapEntry($package->getNamespace(), $package->getPackagePath(), 'Psr4');
			}
		}
	}

	/**
	 * Add a namespace to class path mapping to the class loader for resolving classes.
	 *
	 * @param string $namespace A namespace to map to a class path.
	 * @param string $classPath The class path to be mapped.
	 * @param string $mappingType The mapping type for this mapping entry. Currently one of "Psr0" or "Psr4" will work. Defaults to "Psr4"
	 * @return void
	 */
	public function createNamespaceMapEntry($namespace, $classPath, $mappingType = 'Psr0') {
		$namespaceParts = explode('\\', $namespace);
		// Doctrine has a backslash at the end of their package namespace.
		if (end($namespaceParts) === '') {
			array_pop($namespaceParts);
		}

		$unifiedClassPath = ((substr($classPath, -1, 1) === DIRECTORY_SEPARATOR) ? $classPath : $classPath . '/');

		$currentArray = & $this->packageNamespaces;
		foreach ($namespaceParts as $namespacePart) {
			if (!isset($currentArray[$namespacePart])) {
				$currentArray[$namespacePart] = array();
			}
			$currentArray = & $currentArray[$namespacePart];
		}
		if (!isset($currentArray['_pathData'])) {
			$currentArray['_pathData'] = array();
		}

		$currentArray['_pathData'][md5($unifiedClassPath . '-' . $mappingType)] = array (
			'mappingType' => $mappingType,
			'path' => $unifiedClassPath
		);
	}

	/**
	 * Try to build a path to a class according to PSR-0 rules.
	 *
	 * @param array $classNameParts Parts of the FQ classname.
	 * @param string $classPath Already detected class path to a possible package.
	 * @return string
	 */
	protected function buildClassPathWithPsr0($classNameParts, $classPath) {
		$fileName = implode('/', $classNameParts) . '.php';
		return $classPath . $fileName;
	}

	/**
	 * Try to build a path to a class according to PSR-4 rules.
	 *
	 * @param array $classNameParts Parts of the FQ classname.
	 * @param string $classPath Already detected class path to a possible package.
	 * @param integer $packageNamespacePartCount Amount of parts of the className that is also part of the package namespace.
	 * @return string
	 */
	protected function buildClassPathWithPsr4($classNameParts, $classPath, $packageNamespacePartCount) {
		$fileName = implode('/', array_slice($classNameParts, $packageNamespacePartCount)) . '.php';
		return $classPath . $fileName;
	}

	/**
	 * @param string $composerPath Path to the composer directory (with trailing slash).
	 * @return void
	 */
	protected function initializeComposerAutoloadInformation($composerPath) {
		if (file_exists($composerPath . 'autoload_classmap.php')) {
			$classMap = include($composerPath . 'autoload_classmap.php');
			if ($classMap !== FALSE) {
				$this->classMap = $classMap;
			}
		}

		if (file_exists($composerPath . 'autoload_namespaces.php')) {
			$namespaceMap = include($composerPath . 'autoload_namespaces.php');
			if ($namespaceMap !== FALSE) {
				foreach ($namespaceMap as $namespace => $paths) {
					if (is_array($paths)) {
						foreach ($paths as $path) {
							if ($namespace === '') {
								$this->fallbackClassPaths[] = $path;
							} else {
								$this->createNamespaceMapEntry($namespace, $path);
							}
						}
					} else {
						if ($namespace === '') {
							$this->fallbackClassPaths[] = $paths;
						} else {
							$this->createNamespaceMapEntry($namespace, $paths);
						}
					}
				}
			}
		}

		if (file_exists($composerPath . 'autoload_psr4.php')) {
			$psr4Map = include($composerPath . 'autoload_psr4.php');
			if ($psr4Map !== FALSE) {
				foreach ($psr4Map as $namespace => $possibleClassPaths) {
					if (is_array($possibleClassPaths)) {
						foreach ($possibleClassPaths as $possibleClassPath) {
							if ($namespace === '') {
								$this->fallbackClassPaths[] = $possibleClassPath;
							} else {
								$this->createNamespaceMapEntry($namespace, $possibleClassPath, 'Psr4');
							}
						}
					} else {
						if ($namespace === '') {
							$this->fallbackClassPaths[] = $possibleClassPaths;
						} else {
							$this->createNamespaceMapEntry($namespace, $possibleClassPaths, 'Psr4');
						}
					}
				}
			}
		}

		if (file_exists($composerPath . 'include_paths.php')) {
			$includePaths = include($composerPath . 'include_paths.php');
			if ($includePaths !== FALSE) {
				array_push($includePaths, get_include_path());
				set_include_path(join(PATH_SEPARATOR, $includePaths));
			}
		}

		if (file_exists($composerPath . 'autoload_files.php')) {
			$includeFiles = include($composerPath . 'autoload_files.php');
			if ($includeFiles !== FALSE) {
				foreach ($includeFiles as $file) {
					require_once($file);
				}
			}
		}
	}

	/**
	 * Sets the flag which enables or disables autoloading support for functional
	 * test files.
	 *
	 * @param boolean $flag
	 * @return void
	 */
	public function setConsiderTestsNamespace($flag) {
		$this->considerTestsNamespace = $flag;
	}
}

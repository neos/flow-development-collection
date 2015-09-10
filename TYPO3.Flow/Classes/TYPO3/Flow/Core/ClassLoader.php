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
use TYPO3\Flow\Package;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ClassLoader {

	/**
	 * @var string
	 */
	const MAPPING_TYPE_PSR0 = 'Psr0';

	/**
	 * @var string
	 */
	const MAPPING_TYPE_PSR4 = 'Psr4';

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
	 *
	 */
	protected $nonExistentClasses = array();

	/**
	 * @var array
	 */
	protected $availableProxyClasses;

	/**
	 * @param ApplicationContext $context
	 */
	public function __construct(ApplicationContext $context = NULL) {
		$distributionComposerManifest = json_decode(file_get_contents(FLOW_PATH_ROOT . 'composer.json'));
		$this->defaultVendorDirectory = $distributionComposerManifest->config->{'vendor-dir'};
		$composerPath = FLOW_PATH_ROOT . $this->defaultVendorDirectory . '/composer/';
		$this->initializeAutoloadInformation($composerPath, $context);
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
		if ($this->classesCache !== NULL && ($this->availableProxyClasses === NULL || isset($this->availableProxyClasses[implode('_', $namespaceParts)])) && $this->classesCache->requireOnce(implode('_', $namespaceParts)) !== FALSE) {
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

		// Load classes from the Flow package at a very early stage where no packages have been registered yet:
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

		// This will contain all possible class mappings for the given class name. We start with the fallback paths and prepend mappings with growing specificy.
		$collectedPossibleNamespaceMappings = array(
			array('p' => $this->fallbackClassPaths, 'c' => 0)
		);

		if ($namespacePartCount > 1) {
			while (($packagenamespacePartCount + 1) < $namespacePartCount) {
				$possiblePackageNamespacePart = $namespaceParts[$packagenamespacePartCount];
				if (!isset($currentPackageArray[$possiblePackageNamespacePart])) {
					break;
				}

				$packagenamespacePartCount++;
				$currentPackageArray = $currentPackageArray[$possiblePackageNamespacePart];
				if (isset($currentPackageArray['_pathData'])) {
					array_unshift($collectedPossibleNamespaceMappings, array('p' => $currentPackageArray['_pathData'], 'c' => $packagenamespacePartCount));

				}
			}
		}

		foreach ($collectedPossibleNamespaceMappings as $nameSpaceMapping) {
			if ($this->loadClassFromPossiblePaths($nameSpaceMapping['p'], $namespaceParts, $nameSpaceMapping['c'])) {
				return TRUE;
			}
		}

		$this->nonExistentClasses[$className] = TRUE;
		return FALSE;
	}

	/**
	 * Tries to load a class from a list of possible paths
	 *
	 * @param array $possiblePaths
	 * @param array $namespaceParts
	 * @param integer $packageNamespacePartCount
	 * @return boolean
	 */
	protected function loadClassFromPossiblePaths(array $possiblePaths, array $namespaceParts, $packageNamespacePartCount) {
		foreach ($possiblePaths as $possiblePathData) {
			$pathConstructor = 'buildClassPathWith' . $possiblePathData['mappingType'];
			$possibleFilePath = $this->$pathConstructor($namespaceParts, $possiblePathData['path'], $packageNamespacePartCount);
			if (is_file($possibleFilePath)) {
				$result = include_once($possibleFilePath);
				if ($result !== FALSE) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $allPackages An array of \TYPO3\Flow\Package\Package objects
	 * @param array $activePackages An array of \TYPO3\Flow\Package\Package objects
	 * @return void
	 */
	public function setPackages(array $allPackages, array $activePackages) {
		/** @var Package $package */
		foreach ($allPackages as $packageKey => $package) {
			if (isset($activePackages[$packageKey])) {
				if ($package->getAutoloadType() === Package::AUTOLOADER_TYPE_PSR4) {
					$this->createNamespaceMapEntry($package->getNamespace(), $package->getClassesPath(), self::MAPPING_TYPE_PSR4);
				} else {
					$this->createNamespaceMapEntry($package->getNamespace(), $package->getClassesPath());
				}
				if ($this->considerTestsNamespace) {
					$this->createNamespaceMapEntry($package->getNamespace(), $package->getPackagePath(), self::MAPPING_TYPE_PSR4);
				}
			} else {
				// Remove entries coming from composer for inactive packages.
				if ($package->getAutoloadType() === Package::AUTOLOADER_TYPE_PSR4) {
					$this->removeNamespaceMapEntry($package->getNamespace(), $package->getClassesPath(), self::MAPPING_TYPE_PSR4);
				} else {
					$this->removeNamespaceMapEntry($package->getNamespace(), $package->getClassesPath());
				}
				if ($this->considerTestsNamespace) {
					$this->removeNamespaceMapEntry($package->getNamespace(), $package->getPackagePath(), self::MAPPING_TYPE_PSR4);
				}
			}
		}
	}

	/**
	 * Add a namespace to class path mapping to the class loader for resolving classes.
	 *
	 * @param string $namespace A namespace to map to a class path.
	 * @param string $classPath The class path to be mapped.
	 * @param string $mappingType The mapping type for this mapping entry. Currently one of self::MAPPING_TYPE_PSR0 or self::MAPPING_TYPE_PSR4 will work. Defaults to self::MAPPING_TYPE_PSR0
	 * @return void
	 */
	protected function createNamespaceMapEntry($namespace, $classPath, $mappingType = self::MAPPING_TYPE_PSR0) {
		$unifiedClassPath = ((substr($classPath, -1, 1) === '/') ? $classPath : $classPath . '/');

		$currentArray = & $this->packageNamespaces;
		foreach (explode('\\', rtrim($namespace, '\\')) as $namespacePart) {
			if (!isset($currentArray[$namespacePart])) {
				$currentArray[$namespacePart] = array();
			}
			$currentArray = & $currentArray[$namespacePart];
		}
		if (!isset($currentArray['_pathData'])) {
			$currentArray['_pathData'] = array();
		}

		$currentArray['_pathData'][md5($unifiedClassPath . '-' . $mappingType)] = array(
			'mappingType' => $mappingType,
			'path' => $unifiedClassPath
		);
	}

	/**
	 * Adds an entry to the fallback path map. MappingType for this kind of paths is always PSR4 as no package namespace is used then.
	 *
	 * @param string $path The fallback path to search in.
	 * @return void
	 */
	public function createFallbackPathEntry($path) {
		$entryIdentifier = md5($path);
		if (!isset($this->fallbackClassPaths[$entryIdentifier])) {
			$this->fallbackClassPaths[$entryIdentifier] = array(
				'path' => $path,
				'mappingType' => self::MAPPING_TYPE_PSR4
			);
		}
	}

	/**
	 * Tries to remove a possibly existing namespace to class path map entry.
	 *
	 * @param string $namespace A namespace mapped to a class path.
	 * @param string $classPath The class path to be removed.
	 * @param string $mappingType The mapping type for this mapping entry. Currently one of self::MAPPING_TYPE_PSR0 or self::MAPPING_TYPE_PSR4 will work. Defaults to self::MAPPING_TYPE_PSR0
	 * @return void
	 */
	protected function removeNamespaceMapEntry($namespace, $classPath, $mappingType = self::MAPPING_TYPE_PSR0) {
		$unifiedClassPath = ((substr($classPath, -1, 1) === '/') ? $classPath : $classPath . '/');

		$currentArray = & $this->packageNamespaces;
		foreach (explode('\\', rtrim($namespace, '\\')) as $namespacePart) {
			if (!isset($currentArray[$namespacePart])) {
				return;
			}
			$currentArray = & $currentArray[$namespacePart];
		}
		if (!isset($currentArray['_pathData'])) {
			return;
		}

		if (isset($currentArray['_pathData'][md5($unifiedClassPath . '-' . $mappingType)])) {
			unset ($currentArray['_pathData'][md5($unifiedClassPath . '-' . $mappingType)]);
			if (empty($currentArray['_pathData'])) {
				unset($currentArray['_pathData']);
			}
		}
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
	 * @param ApplicationContext $context
	 * @return void
	 */
	protected function initializeAutoloadInformation($composerPath, ApplicationContext $context = NULL) {
		if (is_file($composerPath . 'autoload_classmap.php')) {
			$classMap = include($composerPath . 'autoload_classmap.php');
			if ($classMap !== FALSE) {
				$this->classMap = $classMap;
			}
		}

		if (is_file($composerPath . 'autoload_namespaces.php')) {
			$namespaceMap = include($composerPath . 'autoload_namespaces.php');
			if ($namespaceMap !== FALSE) {
				foreach ($namespaceMap as $namespace => $paths) {
					if (!is_array($paths)) {
						$paths = array($paths);
					}
					foreach ($paths as $path) {
						if ($namespace === '') {
							$this->createFallbackPathEntry($path);
						} else {
							$this->createNamespaceMapEntry($namespace, $path);
						}
					}
				}
			}
		}

		if (is_file($composerPath . 'autoload_psr4.php')) {
			$psr4Map = include($composerPath . 'autoload_psr4.php');
			if ($psr4Map !== FALSE) {
				foreach ($psr4Map as $namespace => $possibleClassPaths) {
					if (!is_array($possibleClassPaths)) {
						$possibleClassPaths = array($possibleClassPaths);
					}
					foreach ($possibleClassPaths as $possibleClassPath) {
						if ($namespace === '') {
							$this->createFallbackPathEntry($possibleClassPath);
						} else {
							$this->createNamespaceMapEntry($namespace, $possibleClassPath, self::MAPPING_TYPE_PSR4);
						}
					}
				}
			}
		}

		if (is_file($composerPath . 'include_paths.php')) {
			$includePaths = include($composerPath . 'include_paths.php');
			if ($includePaths !== FALSE) {
				array_push($includePaths, get_include_path());
				set_include_path(join(PATH_SEPARATOR, $includePaths));
			}
		}

		if (is_file($composerPath . 'autoload_files.php')) {
			$includeFiles = include($composerPath . 'autoload_files.php');
			if ($includeFiles !== FALSE) {
				foreach ($includeFiles as $file) {
					require_once($file);
				}
			}
		}

		if ($context !== NULL) {
			$this->initializeAvailableProxyClasses($context);
		}
	}

	/**
	 * Initialize available proxy classes from the cached list.
	 *
	 * @param ApplicationContext $context
	 * @return void
	 */
	public function initializeAvailableProxyClasses(ApplicationContext $context) {
		$proxyClasses = @include(FLOW_PATH_DATA . 'Temporary/' . (string)$context . '/AvailableProxyClasses.php');
		if ($proxyClasses !== FALSE) {
			$this->availableProxyClasses = $proxyClasses;
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

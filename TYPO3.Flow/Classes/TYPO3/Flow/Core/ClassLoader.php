<?php
namespace TYPO3\Flow\Core;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package;
use TYPO3\Flow\Utility\Files;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ClassLoader
{
    /**
     * @var string
     */
    const MAPPING_TYPE_PSR0 = 'psr-0';

    /**
     * @var string
     */
    const MAPPING_TYPE_PSR4 = 'psr-4';

    /**
     * @var string
     */
    const MAPPING_TYPE_CLASSMAP = 'classmap';

    /**
     * @var string
     */
    const MAPPING_TYPE_FILES = 'files';

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
    protected $considerTestsNamespace = false;

    /**
     * @var array
     */
    protected $ignoredClassNames = array(
        'integer' => true,
        'string' => true,
        'param' => true,
        'return' => true,
        'var' => true,
        'throws' => true,
        'api' => true,
        'todo' => true,
        'fixme' => true,
        'see' => true,
        'license' => true,
        'author' => true,
        'test' => true,
        'deprecated' => true,
        'internal' => true,
        'since' => true,
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
     * @param array $defaultPackageEntries Adds default entries for packages that should be available for very early loading
     */
    public function __construct(ApplicationContext $context = null, $defaultPackageEntries = [])
    {
        $distributionComposerManifest = json_decode(file_get_contents(FLOW_PATH_ROOT . 'composer.json'));
        $this->defaultVendorDirectory = $distributionComposerManifest->config->{'vendor-dir'};
        $composerPath = FLOW_PATH_ROOT . $this->defaultVendorDirectory . '/composer/';

        foreach ($defaultPackageEntries as $entry) {
            $this->createNamespaceMapEntry($entry['namespace'], $entry['classPath'], $entry['mappingType']);
        }

        $this->initializeAutoloadInformation($composerPath, $context);
    }

    /**
     * Injects the cache for storing the renamed original classes
     *
     * @param \TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache
     * @return void
     */
    public function injectClassesCache(\TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache)
    {
        $this->classesCache = $classesCache;
    }

    /**
     * Loads php files containing classes or interfaces found in the classes directory of
     * a package and specifically registered classes.
     *
     * @param string $className Name of the class/interface to load
     * @return boolean
     */
    public function loadClass($className)
    {
        if ($className[0] === '\\') {
            $className = ltrim($className, '\\');
        }

        $namespaceParts = explode('\\', $className);

        // Workaround for Doctrine's annotation parser which does a class_exists() for annotations like "@param" and so on:
        if (isset($this->ignoredClassNames[$className]) || isset($this->ignoredClassNames[end($namespaceParts)]) || isset($this->nonExistentClasses[$className])) {
            return false;
        }

        // Loads any known proxied class:
        if ($this->classesCache !== null && ($this->availableProxyClasses === null || isset($this->availableProxyClasses[implode('_', $namespaceParts)])) && $this->classesCache->requireOnce(implode('_', $namespaceParts)) !== false) {
            return true;
        }

        if (isset($this->classMap[$className])) {
            include($this->classMap[$className]);
            return true;
        }

        $classNamePart = array_pop($namespaceParts);
        $classNameParts = explode('_', $classNamePart);
        $namespaceParts = array_merge($namespaceParts, $classNameParts);
        $namespacePartCount = count($namespaceParts);

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
                return true;
            }
        }

        $this->nonExistentClasses[$className] = true;
        return false;
    }

    /**
     * Tries to load a class from a list of possible paths. This is needed because packages are not prefix-free; i.e.
     * there may exist a package "Neos" and a package "Neos.NodeTypes" -- so a class Neos\NodeTypes\Foo must be first
     * loaded (if it exists) from Neos.NodeTypes, falling back to Neos afterwards.
     *
     * @param array $possiblePaths
     * @param array $namespaceParts
     * @param integer $packageNamespacePartCount
     * @return boolean
     */
    protected function loadClassFromPossiblePaths(array $possiblePaths, array $namespaceParts, $packageNamespacePartCount)
    {
        foreach ($possiblePaths as $possiblePathData) {
            $possibleFilePath = '';
            if ($possiblePathData['mappingType'] === self::MAPPING_TYPE_PSR0) {
                $possibleFilePath = $this->buildClassPathWithPsr0($namespaceParts, $possiblePathData['path'], $packageNamespacePartCount);
            }
            if ($possiblePathData['mappingType'] === self::MAPPING_TYPE_PSR4) {
                $possibleFilePath = $this->buildClassPathWithPsr4($namespaceParts, $possiblePathData['path'], $packageNamespacePartCount);
            }

            if (is_file($possibleFilePath)) {
                $result = include_once($possibleFilePath);
                if ($result !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sets the available packages
     *
     * @param array $activePackages An array of \TYPO3\Flow\Package\Package objects
     * @return void
     */
    public function setPackages(array $activePackages)
    {
        /** @var Package $package */
        foreach ($activePackages as $packageKey => $package) {
            foreach ($package->getFlattenedAutoloadConfiguration() as $configuration) {
                $this->createNamespaceMapEntry($configuration['namespace'], $configuration['classPath'], $configuration['mappingType']);
            }
            // TODO: Replace with "autoload-dev" usage
            if ($this->considerTestsNamespace) {
                $this->createNamespaceMapEntry($package->getNamespace(), $package->getPackagePath(), self::MAPPING_TYPE_PSR4);
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
    protected function createNamespaceMapEntry($namespace, $classPath, $mappingType = self::MAPPING_TYPE_PSR0)
    {
        $unifiedClassPath = Files::getNormalizedPath($classPath);
        $entryIdentifier = md5($unifiedClassPath . '-' . $mappingType);

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

        $currentArray['_pathData'][$entryIdentifier] = array(
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
    public function createFallbackPathEntry($path)
    {
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
    protected function removeNamespaceMapEntry($namespace, $classPath, $mappingType = self::MAPPING_TYPE_PSR0)
    {
        $unifiedClassPath = Files::getNormalizedPath($classPath);
        $entryIdentifier = md5($unifiedClassPath . '-' . $mappingType);

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

        if (isset($currentArray['_pathData'][$entryIdentifier])) {
            unset($currentArray['_pathData'][$entryIdentifier]);
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
    protected function buildClassPathWithPsr0($classNameParts, $classPath)
    {
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
    protected function buildClassPathWithPsr4($classNameParts, $classPath, $packageNamespacePartCount)
    {
        $fileName = implode('/', array_slice($classNameParts, $packageNamespacePartCount)) . '.php';

        return $classPath . $fileName;
    }

    /**
     * @param string $composerPath Path to the composer directory (with trailing slash).
     * @param ApplicationContext $context
     * @return void
     */
    protected function initializeAutoloadInformation($composerPath, ApplicationContext $context = null)
    {
        if (is_file($composerPath . 'autoload_classmap.php')) {
            $classMap = include($composerPath . 'autoload_classmap.php');
            if ($classMap !== false) {
                $this->classMap = $classMap;
            }
        }

        if (is_file($composerPath . 'autoload_namespaces.php')) {
            $namespaceMap = include($composerPath . 'autoload_namespaces.php');
            if ($namespaceMap !== false) {
                foreach ($namespaceMap as $namespace => $paths) {
                    if (!is_array($paths)) {
                        $paths = [$paths];
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
            if ($psr4Map !== false) {
                foreach ($psr4Map as $namespace => $possibleClassPaths) {
                    if (!is_array($possibleClassPaths)) {
                        $possibleClassPaths = [$possibleClassPaths];
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
            if ($includePaths !== false) {
                array_push($includePaths, get_include_path());
                set_include_path(implode(PATH_SEPARATOR, $includePaths));
            }
        }

        if (is_file($composerPath . 'autoload_files.php')) {
            $includeFiles = include($composerPath . 'autoload_files.php');
            if ($includeFiles !== false) {
                foreach ($includeFiles as $file) {
                    require_once($file);
                }
            }
        }

        if ($context !== null) {
            $proxyClasses = @include(FLOW_PATH_TEMPORARY . 'AvailableProxyClasses.php');
            if ($proxyClasses !== false) {
                $this->availableProxyClasses = $proxyClasses;
            }
        }
    }

    /**
     * Initialize available proxy classes from the cached list.
     *
     * @param ApplicationContext $context
     * @return void
     */
    public function initializeAvailableProxyClasses(ApplicationContext $context)
    {
        $proxyClasses = @include(FLOW_PATH_DATA . 'Temporary/' . (string)$context . '/AvailableProxyClasses.php');
        if ($proxyClasses !== false) {
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
    public function setConsiderTestsNamespace($flag)
    {
        $this->considerTestsNamespace = $flag;
    }

    /**
     * Is the given mapping type predictable in terms of path to class name
     *
     * @param string $mappingType
     * @return boolean
     */
    public static function isAutoloadTypeWithPredictableClassPath($mappingType)
    {
        return ($mappingType === static::MAPPING_TYPE_PSR0 || $mappingType === static::MAPPING_TYPE_PSR4);
    }
}

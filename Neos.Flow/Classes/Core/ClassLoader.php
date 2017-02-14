<?php
namespace Neos\Flow\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Flow\Package;
use Neos\Utility\Files;

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
     * @var PhpFrontend
     */
    protected $classesCache;

    /**
     * A list of namespaces this class loader is definitely responsible for.
     *
     * @var array
     */
    protected $packageNamespaces = [];

    /**
     * @var boolean
     */
    protected $considerTestsNamespace = false;

    /**
     * @var array
     */
    protected $ignoredClassNames = [
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
    ];

    /**
     * @var array
     */
    protected $fallbackClassPaths = [];

    /**
     * Cache classNames that were not found in this class loader in order
     * to save time in resolving those non existent classes.
     * Usually these will be annotations that have no class.
     *
     * @var array
     *
     */
    protected $nonExistentClasses = [];

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
        foreach ($defaultPackageEntries as $entry) {
            $this->createNamespaceMapEntry($entry['namespace'], $entry['classPath'], $entry['mappingType']);
        }

        $this->initializeAvailableProxyClasses($context);
    }

    /**
     * Injects the cache for storing the renamed original classes
     *
     * @param PhpFrontend $classesCache
     * @return void
     */
    public function injectClassesCache(PhpFrontend $classesCache)
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

        $classNamePart = array_pop($namespaceParts);
        $classNameParts = explode('_', $classNamePart);
        $namespaceParts = array_merge($namespaceParts, $classNameParts);
        $namespacePartCount = count($namespaceParts);

        $currentPackageArray = $this->packageNamespaces;
        $packagenamespacePartCount = 0;

        // This will contain all possible class mappings for the given class name. We start with the fallback paths and prepend mappings with growing specificy.
        $collectedPossibleNamespaceMappings = [
            ['p' => $this->fallbackClassPaths, 'c' => 0]
        ];

        if ($namespacePartCount > 1) {
            while (($packagenamespacePartCount + 1) < $namespacePartCount) {
                $possiblePackageNamespacePart = $namespaceParts[$packagenamespacePartCount];
                if (!isset($currentPackageArray[$possiblePackageNamespacePart])) {
                    break;
                }

                $packagenamespacePartCount++;
                $currentPackageArray = $currentPackageArray[$possiblePackageNamespacePart];
                if (isset($currentPackageArray['_pathData'])) {
                    array_unshift($collectedPossibleNamespaceMappings, ['p' => $currentPackageArray['_pathData'], 'c' => $packagenamespacePartCount]);
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
     * @param array $activePackages An array of \Neos\Flow\Package\Package objects
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
                foreach ($package->getNamespaces() as $namespace) {
                    $this->createNamespaceMapEntry($namespace, $package->getPackagePath(), self::MAPPING_TYPE_PSR4);
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
    protected function createNamespaceMapEntry($namespace, $classPath, $mappingType = self::MAPPING_TYPE_PSR0)
    {
        $unifiedClassPath = Files::getNormalizedPath($classPath);
        $entryIdentifier = md5($unifiedClassPath . '-' . $mappingType);

        $currentArray = & $this->packageNamespaces;
        foreach (explode('\\', rtrim($namespace, '\\')) as $namespacePart) {
            if (!isset($currentArray[$namespacePart])) {
                $currentArray[$namespacePart] = [];
            }
            $currentArray = & $currentArray[$namespacePart];
        }
        if (!isset($currentArray['_pathData'])) {
            $currentArray['_pathData'] = [];
        }

        $currentArray['_pathData'][$entryIdentifier] = [
            'mappingType' => $mappingType,
            'path' => $unifiedClassPath
        ];
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
            $this->fallbackClassPaths[$entryIdentifier] = [
                'path' => $path,
                'mappingType' => self::MAPPING_TYPE_PSR4
            ];
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
     * Initialize available proxy classes from the cached list.
     *
     * @param ApplicationContext $context
     * @return void
     */
    public function initializeAvailableProxyClasses(ApplicationContext $context = null)
    {
        if ($context === null) {
            return;
        }

        $proxyClasses = @include(FLOW_PATH_TEMPORARY_BASE . '/' . (string)$context . '/AvailableProxyClasses.php');
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

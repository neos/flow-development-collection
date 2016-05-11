<?php
namespace TYPO3\Flow\Package;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Composer\ComposerUtility;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Core\ClassLoader;
use TYPO3\Flow\Utility\Files;

/**
 * A Package
 *
 * @api
 */
class Package implements PackageInterface
{
    /**
     * Unique key of this package. Example for the Flow package: "TYPO3.Flow"
     *
     * @var string
     */
    protected $packageKey;

    /**
     * composer name for this package
     *
     * @var string
     */
    protected $composerName;

    /**
     * Full path to this package's main directory
     *
     * @var string
     */
    protected $packagePath;

    /**
     * If this package is protected and therefore cannot be deactivated or deleted
     *
     * @var boolean
     * @api
     */
    protected $protected = false;

    /**
     * Meta information about this package
     *
     * @var \TYPO3\Flow\Package\MetaData
     * TODO: Remove after deprecation period (Flow 4.0)
     */
    protected $packageMetaData;

    /**
     * The namespace of the classes contained in this package
     *
     * @var string
     */
    protected $namespace;

    /**
     * Array of all declared autoload namespaces contained in this package
     *
     * @var string[]
     */
    protected $namespaces;

    /**
     * @var string[]
     */
    protected $autoloadTypes;

    /**
     * If enabled, the files in the Classes directory are registered and Reflection, Dependency Injection, AOP etc. are supported.
     * Disable this flag if you don't need object management for your package and want to save some memory.
     *
     * @var boolean
     * @api
     */
    protected $objectManagementEnabled = true;

    /**
     * @var array
     */
    protected $autoloadConfiguration;

    /**
     * @var array
     */
    protected $flattenedAutoloadConfiguration;

    /**
     * Constructor
     *
     * @param string $packageKey Key of this package
     * @param string $composerName
     * @param string $packagePath Absolute path to the location of the package's composer manifest
     * @param array $autoloadConfiguration
     * @throws Exception\InvalidPackageKeyException
     */
    public function __construct($packageKey, $composerName, $packagePath, array $autoloadConfiguration = [])
    {
        $this->autoloadConfiguration = $autoloadConfiguration;
        $this->packagePath = Files::getNormalizedPath($packagePath);
        $this->packageKey = $packageKey;
        $this->composerName = $composerName;
    }

    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
    }

    /**
     * Returns the package meta data object of this package.
     * Note that since Flow 3.1 the MetaData won't contain any constraints,
     * please use the composer manifest directly if you need this information.
     *
     * @return \TYPO3\Flow\Package\MetaData
     * @deprecated To be removed in Flow 4.0
     */
    public function getPackageMetaData()
    {
        if ($this->packageMetaData === null) {
            $this->packageMetaData = new MetaData($this->getPackageKey());
            $this->packageMetaData->setDescription($this->getComposerManifest('description'));
            $this->packageMetaData->setVersion($this->getComposerManifest('version'));
            $this->packageMetaData->setPackageType($this->getComposerManifest('type'));
        }

        return $this->packageMetaData;
    }

    /**
     * Returns the array of filenames of the class files
     *
     * @return \Generator A Generator for class names (key) and their filename, including the absolute path.
     */
    public function getClassFiles()
    {
        foreach ($this->getFlattenedAutoloadConfiguration() as $configuration) {
            $normalizedAutoloadPath = $this->normalizeAutoloadPath($configuration['mappingType'], $configuration['namespace'], $configuration['classPath']);
            if (!is_dir($normalizedAutoloadPath)) {
                continue;
            }
            foreach ($this->getClassesInNormalizedAutoloadPath($normalizedAutoloadPath, $configuration['namespace']) as $className => $classPath) {
                yield $className => $classPath;
            }
        }
    }

    /**
     * Returns the array of filenames of class files provided by functional tests contained in this package
     *
     * @return array An array of class names (key) and their filename, including the relative path to the package's directory
     */
    public function getFunctionalTestsClassFiles()
    {
        if (is_dir($this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL)) {
            // TODO REFACTOR replace with usage of "autoload-dev"
            $namespacePrefix = str_replace('/', '\\', Files::concatenatePaths([
                $this->getNamespace(),
                '\\Tests\\Functional\\'
            ]));
            foreach ($this->getClassesInNormalizedAutoloadPath($this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL, $namespacePrefix) as $className => $classPath) {
                yield $className => $classPath;
            }
        }
    }

    /**
     * Returns the package key of this package.
     *
     * @return string
     * @api
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    /**
     * Returns the packages composer name
     *
     * @return string
     * TODO: Should be added to the interface in the next major Flow version (4.0)
     */
    public function getComposerName()
    {
        return $this->composerName;
    }

    /**
     * Returns array of all declared autoload namespaces contained in this package
     *
     * @return array
     * @api
     * TODO: Should be added to the interface in the next major Flow version (4.0)
     */
    public function getNamespaces()
    {
        if ($this->namespaces === null) {
            $this->explodeAutoloadConfiguration();
        }

        return $this->namespaces;
    }

    /**
     * Returns the PHP namespace of classes in this package.
     *
     * @return string
     * @api
     * @deprecated see getNamespaces()
     */
    public function getNamespace()
    {
        $allNamespaces = $this->getNamespaces();

        return reset($allNamespaces);
    }

    /**
     * @return string[]
     */
    public function getAutoloadTypes()
    {
        if ($this->autoloadTypes === null) {
            $this->explodeAutoloadConfiguration();
        }

        return $this->autoloadTypes;
    }

    /**
     * PSR autoloading type
     *
     * @return string see self::AUTOLOADER_TYPE_* - NULL in case it is not defined or unknown
     * @deprecated see getAutoloadTypes()
     */
    public function getAutoloadType()
    {
        $autoloadConfigurations = $this->getFlattenedAutoloadConfiguration();
        $firstAutoload = reset($autoloadConfigurations);

        if ($firstAutoload === false) {
            return null;
        }

        return $firstAutoload['mappingType'];
    }

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return boolean
     * @api
     */
    public function isProtected()
    {
        return $this->protected;
    }

    /**
     * Tells if files in the Classes directory should be registered and object management enabled for this package.
     *
     * @return boolean
     */
    public function isObjectManagementEnabled()
    {
        return $this->objectManagementEnabled;
    }

    /**
     * Sets the protection flag of the package
     *
     * @param boolean $protected TRUE if the package should be protected, otherwise FALSE
     * @return void
     * @api
     */
    public function setProtected($protected)
    {
        $this->protected = (boolean)$protected;
    }

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     * @api
     */
    public function getPackagePath()
    {
        return $this->packagePath;
    }

    /**
     * Returns the full path to this package's Classes directory
     *
     * @return string Path to this package's Classes directory
     * @api
     * @deprecated
     */
    public function getClassesPath()
    {
        $autoloadConfigurations = $this->getFlattenedAutoloadConfiguration();
        $firstAutoload = reset($autoloadConfigurations);

        if ($firstAutoload === false) {
            return null;
        }

        return $firstAutoload['classPath'];
    }

    /**
     * @return array
     */
    public function getAutoloadPaths()
    {
        return array_map(function ($configuration) {
            return $configuration['classPath'];
        }, $this->getFlattenedAutoloadConfiguration());
    }

    /**
     * Returns the full path to the package's classes namespace entry path,
     * e.g. "My.Package/ClassesPath/My/Package/"
     *
     * @return string Path to this package's autoload directory
     * @api
     * @deprecated
     */
    public function getClassesNamespaceEntryPath()
    {
        $autoloadConfigurations = $this->getFlattenedAutoloadConfiguration();
        $firstAutoload = reset($autoloadConfigurations);

        $basePath = $firstAutoload['classPath'];

        $pathifiedNamespace = '';
        if ($firstAutoload['mappingType'] === ClassLoader::MAPPING_TYPE_PSR0) {
            $pathifiedNamespace = str_replace('\\', '/', $firstAutoload['namespace']);
        }

        return Files::concatenatePaths([$basePath, $pathifiedNamespace]) . '/';
    }

    /**
     * Returns the full path to this package's functional tests directory
     *
     * @return string Path to this package's functional tests directory
     * @api
     * TODO: Should be replaced by using autoload-dev
     */
    public function getFunctionalTestsPath()
    {
        return $this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL;
    }

    /**
     * Returns the full path to this package's Resources directory
     *
     * @return string Path to this package's Resources directory
     * @api
     */
    public function getResourcesPath()
    {
        return $this->packagePath . self::DIRECTORY_RESOURCES;
    }

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     * @api
     */
    public function getConfigurationPath()
    {
        return $this->packagePath . self::DIRECTORY_CONFIGURATION;
    }

    /**
     * Returns the full path to the package's meta data directory
     *
     * @return string Full path to the package's meta data directory
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getMetaPath()
    {
        return $this->packagePath . self::DIRECTORY_METADATA;
    }

    /**
     * Returns the full path to the package's documentation directory
     *
     * @return string Full path to the package's documentation directory
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getDocumentationPath()
    {
        return $this->packagePath . self::DIRECTORY_DOCUMENTATION;
    }

    /**
     * Get the autoload configuration for this package. Any valid composer "autoload" configuration.
     *
     * @return array
     */
    public function getAutoloadConfiguration()
    {
        return $this->autoloadConfiguration;
    }

    /**
     * Get a flattened array of autoload configurations that have a predictable pattern (PSR-0, PSR-4)
     *
     * @return array Keys: "namespace", "classPath", "mappingType"
     */
    public function getFlattenedAutoloadConfiguration()
    {
        if ($this->flattenedAutoloadConfiguration === null) {
            $this->explodeAutoloadConfiguration();
        }

        return $this->flattenedAutoloadConfiguration;
    }

    /**
     * Returns contents of Composer manifest - or part there of.
     *
     * @param string $key Optional. Only return the part of the manifest indexed by 'key'
     * @return array|mixed
     * @api
     * TODO: Should be added to the interface in the next major Flow version (4.0)
     */
    public function getComposerManifest($key = null)
    {
        return ComposerUtility::getComposerManifest($this->packagePath, $key);
    }

    /**
     * Get the installed package version (from composer) and as fallback the version given by package meta data.
     *
     * @return string
     * @api
     * TODO: Should be added to the interface in the next major Flow version (4.0)
     */
    public function getInstalledVersion()
    {
        $installedVersion = PackageManager::getPackageVersion($this->composerName);
        return  $installedVersion !== '' ? $installedVersion : $this->getPackageMetaData()->getVersion();
    }

    /**
     * @param string $autoloadType
     * @param string $autoloadNamespace
     * @param string $autoloadPath
     * @return string
     */
    protected function normalizeAutoloadPath($autoloadType, $autoloadNamespace, $autoloadPath)
    {
        $normalizedAutoloadPath = $autoloadPath;
        if ($autoloadType === ClassLoader::MAPPING_TYPE_PSR0) {
            $normalizedAutoloadPath = Files::concatenatePaths([
                    $autoloadPath,
                    str_replace('\\', '/', $autoloadNamespace)
                ]) . '/';
        }
        if ($autoloadType === ClassLoader::MAPPING_TYPE_PSR4) {
            $normalizedAutoloadPath = rtrim($normalizedAutoloadPath, '/') . '/';
        }

        return $normalizedAutoloadPath;
    }

    /**
     * @param string $baseAutoloadPath
     * @param string $autoloadNamespace
     * @return \Generator
     */
    protected function getClassesInNormalizedAutoloadPath($baseAutoloadPath, $autoloadNamespace)
    {
        $autoloadNamespace = trim($autoloadNamespace, '\\') . '\\';
        $directories = [''];
        while ($directories !== []) {
            $currentRelativeDirectory = array_pop($directories);
            $currentAbsoluteDirectory = $baseAutoloadPath . $currentRelativeDirectory;
            if ($handle = opendir($currentAbsoluteDirectory)) {
                while (false !== ($filename = readdir($handle))) {
                    if ($filename[0] === '.') {
                        continue;
                    }
                    $pathAndFilename = $currentAbsoluteDirectory . $filename;
                    if (is_dir($pathAndFilename)) {
                        $directories[] = $currentRelativeDirectory . $filename . '/';
                        continue;
                    }
                    if (strpos(strrev($filename), 'php.') === 0) {
                        $potentialClassNamespace = $autoloadNamespace . str_replace('/', '\\', $currentRelativeDirectory) . basename($filename, '.php');
                        yield $potentialClassNamespace => $pathAndFilename;
                    }
                }
                closedir($handle);
            }
        }
    }

    /**
     * Brings the composer autoload configuration into an easy to use format for various parts of Flow.
     *
     * @return void
     */
    protected function explodeAutoloadConfiguration()
    {
        $this->namespaces = [];
        $this->autoloadTypes = [];
        $this->flattenedAutoloadConfiguration = [];
        $allAutoloadConfiguration = $this->autoloadConfiguration;
        foreach ($allAutoloadConfiguration as $autoloadType => $autoloadConfiguration) {
            $this->autoloadTypes[] = $autoloadType;
            if (ClassLoader::isAutoloadTypeWithPredictableClassPath($autoloadType)) {
                $this->namespaces = array_merge($this->namespaces, array_keys($autoloadConfiguration));
                foreach ($autoloadConfiguration as $namespace => $paths) {
                    $paths = (array)$paths;
                    foreach ($paths as $path) {
                        $this->flattenedAutoloadConfiguration[] = [
                            'namespace' => $namespace,
                            'classPath' => $this->packagePath . $path,
                            'mappingType' => $autoloadType
                        ];
                    }
                }
            }
        }
    }

    /**
     * Returns the available documentations for this package
     *
     * @return array Array of \TYPO3\Flow\Package\Documentation
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getPackageDocumentations()
    {
        $documentations = [];
        $documentationPath = $this->getDocumentationPath();
        if (is_dir($documentationPath)) {
            $documentationsDirectoryIterator = new \DirectoryIterator($documentationPath);
            $documentationsDirectoryIterator->rewind();
            while ($documentationsDirectoryIterator->valid()) {
                $filename = $documentationsDirectoryIterator->getFilename();
                if ($filename[0] != '.' && $documentationsDirectoryIterator->isDir()) {
                    $filename = $documentationsDirectoryIterator->getFilename();
                    $documentation = new Documentation($this, $filename, $documentationPath . $filename . '/');
                    $documentations[$filename] = $documentation;
                }

                $documentationsDirectoryIterator->next();
            }
        }

        return $documentations;
    }
}

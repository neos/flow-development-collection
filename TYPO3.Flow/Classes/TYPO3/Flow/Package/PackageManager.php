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

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Core\ClassLoader;
use TYPO3\Flow\Log\EarlyLogger;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Package\Exception\MissingPackageManifestException;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\SignalSlot\Dispatcher;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\OpcodeCacheHelper;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Flow\Utility\Exception as UtilityException;
use TYPO3\Flow\Package\Exception as PackageException;

/**
 * The default Flow Package Manager
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PackageManager implements PackageManagerInterface
{
    /**
     * @var ClassLoader
     */
    protected $classLoader;

    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var PackageFactory
     */
    protected $packageFactory;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected static $composerLockCache = null;

    /**
     * Array of available packages, indexed by package key (case sensitive)
     * @var array
     */
    protected $packages = array();

    /**
     * A translation table between lower cased and upper camel cased package keys
     * @var array
     */
    protected $packageKeys = array();

    /**
     * A map between ComposerName and PackageKey, only available when scanAvailablePackages is run
     * @var array
     */
    protected $composerNameToPackageKeyMap = array();

    /**
     * List of active packages as package key => package object
     * @var array
     */
    protected $activePackages = array();

    /**
     * Absolute path leading to the various package directories
     * @var string
     */
    protected $packagesBasePath = FLOW_PATH_PACKAGES;

    /**
     * @var string
     */
    protected $packageStatesPathAndFilename;

    /**
     * Package states configuration as stored in the PackageStates.php file
     * @var array
     */
    protected $packageStatesConfiguration = array();

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Cached composer manifest data for this request
     */
    protected static $composerManifestData = array();

    /**
     * @param ClassLoader $classLoader
     * @return void
     */
    public function injectClassLoader(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        if ($this->systemLogger instanceof EarlyLogger) {
            $this->systemLogger->replayLogsOn($systemLogger);
            unset($this->systemLogger);
        }
        $this->systemLogger = $systemLogger;
    }

    /**
     * Initializes the package manager
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function initialize(Bootstrap $bootstrap)
    {
        $this->systemLogger = new EarlyLogger();

        $this->bootstrap = $bootstrap;
        $this->packageStatesPathAndFilename = $this->packageStatesPathAndFilename ?: FLOW_PATH_CONFIGURATION . 'PackageStates.php';
        $this->packageFactory = new PackageFactory($this);

        $this->loadPackageStates();

        $this->activePackages = array();
        foreach ($this->packages as $packageKey => $package) {
            if ($package->isProtected() || (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active')) {
                $this->activePackages[$packageKey] = $package;
            }
        }

        $this->classLoader->setPackages($this->packages, $this->activePackages);

        foreach ($this->activePackages as $package) {
            $package->boot($bootstrap);
        }
    }

    /**
     * Returns TRUE if a package is available (the package's files exist in the packages directory)
     * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
     *
     * @param string $packageKey The key of the package to check
     * @return boolean TRUE if the package is available, otherwise FALSE
     * @api
     */
    public function isPackageAvailable($packageKey)
    {
        $packageKey = $this->getCaseSensitivePackageKey($packageKey);
        return (isset($this->packages[$packageKey]));
    }

    /**
     * Returns TRUE if a package is activated or FALSE if it's not.
     *
     * @param string $packageKey The key of the package to check
     * @return boolean TRUE if package is active, otherwise FALSE
     * @api
     */
    public function isPackageActive($packageKey)
    {
        return (isset($this->activePackages[$packageKey]));
    }

    /**
     * Returns the base path for packages
     *
     * @return string
     */
    public function getPackagesBasePath()
    {
        return $this->packagesBasePath;
    }

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid MetaData information.
     *
     * @param string $packageKey
     * @return PackageInterface The requested package object
     * @throws Exception\UnknownPackageException if the specified package is not known
     * @api
     */
    public function getPackage($packageKey)
    {
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\UnknownPackageException('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
        }
        return $this->packages[$packageKey];
    }

    /**
     * Finds a package by a given object of that package; if no such package
     * could be found, NULL is returned. This basically works with comparing the package class' namespace
     * against the fully qualified class name of the given $object.
     * In order to not being satisfied with a shorter package's namespace, the packages to check are sorted
     * by the length of their namespace descending.
     *
     * @param object $object The object to find the possessing package of
     * @return PackageInterface The package the given object belongs to or NULL if it could not be found
     */
    public function getPackageOfObject($object)
    {
        return $this->getPackageByClassName(TypeHandling::getTypeForValue($object));
    }

    /**
     * Finds a package by a given class name of that package, @see getPackageOfObject().
     *
     * @param string $className The fully qualified class name to find the possessing package of
     * @return PackageInterface The package the given object belongs to or NULL if it could not be found
     */
    public function getPackageByClassName($className)
    {
        $sortedAvailablePackages = $this->getAvailablePackages();
        usort($sortedAvailablePackages, function (PackageInterface $packageOne, PackageInterface $packageTwo) {
            return strlen($packageTwo->getNamespace()) - strlen($packageOne->getNamespace());
        });

        /** @var $package PackageInterface */
        foreach ($sortedAvailablePackages as $package) {
            if (strpos($className, $package->getNamespace()) === 0) {
                return $package;
            }
        }
        return null;
    }

    /**
     * Returns an array of PackageInterface objects of all available packages.
     * A package is available, if the package directory contains valid meta information.
     *
     * @return array<PackageInterface>
     * @api
     */
    public function getAvailablePackages()
    {
        return $this->packages;
    }

    /**
     * Returns an array of PackageInterface objects of all active packages.
     * A package is active, if it is available and has been activated in the package
     * manager settings.
     *
     * @return array <PackageInterface>
     * @api
     */
    public function getActivePackages()
    {
        return $this->activePackages;
    }

    /**
     * Returns an array of PackageInterface objects of all frozen packages.
     * A frozen package is not considered by file monitoring and provides some
     * precompiled reflection data in order to improve performance.
     *
     * @return array<PackageInterface>
     */
    public function getFrozenPackages()
    {
        $frozenPackages = array();
        if ($this->bootstrap->getContext()->isDevelopment()) {
            foreach ($this->packages as $packageKey => $package) {
                if (isset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']) &&
                        $this->packageStatesConfiguration['packages'][$packageKey]['frozen'] === true) {
                    $frozenPackages[$packageKey] = $package;
                }
            }
        }
        return $frozenPackages;
    }

    /**
     * Returns an array of PackageInterface objects of all packages that match
     * the given package state, path, and type filters. All three filters must match, if given.
     *
     * @param string $packageState defaults to available
     * @param string $packagePath
     * @param string $packageType
     *
     * @return array<PackageInterface>
     * @throws Exception\InvalidPackageStateException
     * @api
     */
    public function getFilteredPackages($packageState = 'available', $packagePath = null, $packageType = null)
    {
        $packages = array();
        switch (strtolower($packageState)) {
            case 'available':
                $packages = $this->getAvailablePackages();
            break;
            case 'active':
                $packages = $this->getActivePackages();
            break;
            case 'frozen':
                $packages = $this->getFrozenPackages();
            break;
            default:
                throw new Exception\InvalidPackageStateException('The package state "' . $packageState . '" is invalid', 1372458274);
        }

        if ($packagePath !== null) {
            $packages = $this->filterPackagesByPath($packages, $packagePath);
        }
        if ($packageType !== null) {
            $packages = $this->filterPackagesByType($packages, $packageType);
        }

        return $packages;
    }

    /**
     * Returns an array of PackageInterface objects in the given array of packages
     * that are in the specified Package Path
     *
     * @param array $packages Array of PackageInterface to be filtered
     * @param string $filterPath Filter out anything that's not in this path
     * @return array<PackageInterface>
     */
    protected function filterPackagesByPath(&$packages, $filterPath)
    {
        $filteredPackages = array();
        /** @var $package Package */
        foreach ($packages as $package) {
            $packagePath = substr($package->getPackagePath(), strlen(FLOW_PATH_PACKAGES));
            $packageGroup = substr($packagePath, 0, strpos($packagePath, '/'));
            if ($packageGroup === $filterPath) {
                $filteredPackages[$package->getPackageKey()] = $package;
            }
        }
        return $filteredPackages;
    }

    /**
     * Returns an array of PackageInterface objects in the given array of packages
     * that are of the specified package type.
     *
     * @param array $packages Array of PackageInterface objects to be filtered
     * @param string $packageType Filter out anything that's not of this packageType
     * @return array<PackageInterface>
     */
    protected function filterPackagesByType(&$packages, $packageType)
    {
        $filteredPackages = array();
        /** @var $package Package */
        foreach ($packages as $package) {
            if ($package->getComposerManifest('type') === $packageType) {
                $filteredPackages[$package->getPackageKey()] = $package;
            }
        }
        return $filteredPackages;
    }

    /**
     * Returns the upper camel cased version of the given package key or FALSE
     * if no such package is available.
     *
     * @param string $unknownCasedPackageKey The package key to convert
     * @return mixed The upper camel cased package key or FALSE if no such package exists
     * @api
     */
    public function getCaseSensitivePackageKey($unknownCasedPackageKey)
    {
        $lowerCasedPackageKey = strtolower($unknownCasedPackageKey);
        return (isset($this->packageKeys[$lowerCasedPackageKey])) ? $this->packageKeys[$lowerCasedPackageKey] : false;
    }

    /**
     * Resolves a Flow package key from a composer package name.
     *
     * @param string $composerName
     * @return string
     * @throws Exception\InvalidPackageStateException
     */
    public function getPackageKeyFromComposerName($composerName)
    {
        if (count($this->composerNameToPackageKeyMap) === 0) {
            foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $packageStateConfiguration) {
                $this->composerNameToPackageKeyMap[strtolower($packageStateConfiguration['composerName'])] = $packageKey;
            }
        }
        $lowercasedComposerName = strtolower($composerName);
        if (!isset($this->composerNameToPackageKeyMap[$lowercasedComposerName])) {
            throw new Exception\InvalidPackageStateException('Could not find package with composer name "' . $composerName . '" in PackageStates configuration.', 1352320649);
        }
        return $this->composerNameToPackageKeyMap[$lowercasedComposerName];
    }

    /**
     * Check the conformance of the given package key
     *
     * @param string $packageKey The package key to validate
     * @return boolean If the package key is valid, returns TRUE otherwise FALSE
     * @api
     */
    public function isPackageKeyValid($packageKey)
    {
        return preg_match(PackageInterface::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1;
    }

    /**
     * Create a package, given the package key
     *
     * @param string $packageKey The package key of the new package
     * @param MetaData $packageMetaData If specified, this package meta object is used for writing the Package.xml file, otherwise a rudimentary Package.xml file is created
     * @param string $packagesPath If specified, the package will be created in this path, otherwise the default "Application" directory is used
     * @param string $packageType If specified, the package type will be set, otherwise it will default to "typo3-flow-package"
     * @return PackageInterface The newly created package
     * @throws Exception
     * @throws Exception\PackageKeyAlreadyExistsException
     * @throws Exception\InvalidPackageKeyException
     * @api
     */
    public function createPackage($packageKey, MetaData $packageMetaData = null, $packagesPath = null, $packageType = 'typo3-flow-package')
    {
        if (!$this->isPackageKeyValid($packageKey)) {
            throw new Exception\InvalidPackageKeyException('The package key "' . $packageKey . '" is invalid', 1220722210);
        }
        if ($this->isPackageAvailable($packageKey)) {
            throw new Exception\PackageKeyAlreadyExistsException('The package key "' . $packageKey . '" already exists', 1220722873);
        }

        if ($packagesPath === null) {
            if (is_array($this->settings['package']['packagesPathByType']) && isset($this->settings['package']['packagesPathByType'][$packageType])) {
                $packagesPath = $this->settings['package']['packagesPathByType'][$packageType];
            } else {
                $packagesPath = 'Application';
            }
            $packagesPath = Files::getUnixStylePath(Files::concatenatePaths(array($this->packagesBasePath, $packagesPath)));
        }

        if ($packageMetaData === null) {
            $packageMetaData = new MetaData($packageKey);
        }
        if ($packageMetaData->getPackageType() === null) {
            $packageMetaData->setPackageType($packageType);
        }

        $packagePath = Files::concatenatePaths(array($packagesPath, $packageKey)) . '/';
        Files::createDirectoryRecursively($packagePath);

        foreach (
            array(
                PackageInterface::DIRECTORY_METADATA,
                PackageInterface::DIRECTORY_CLASSES,
                PackageInterface::DIRECTORY_CONFIGURATION,
                PackageInterface::DIRECTORY_DOCUMENTATION,
                PackageInterface::DIRECTORY_RESOURCES,
                PackageInterface::DIRECTORY_TESTS_UNIT,
                PackageInterface::DIRECTORY_TESTS_FUNCTIONAL,
            ) as $path) {
            Files::createDirectoryRecursively(Files::concatenatePaths(array($packagePath, $path)));
        }

        $this->writeComposerManifest($packagePath, $packageKey, $packageMetaData);

        $packagePath = str_replace($this->packagesBasePath, '', $packagePath);
        $package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageKey, PackageInterface::DIRECTORY_CLASSES);

        $this->packages[$packageKey] = $package;
        foreach (array_keys($this->packages) as $upperCamelCasedPackageKey) {
            $this->packageKeys[strtolower($upperCamelCasedPackageKey)] = $upperCamelCasedPackageKey;
        }

        $this->activatePackage($packageKey);

        return $package;
    }

    /**
     * Write a composer manifest for the package.
     *
     * @param string $manifestPath
     * @param string $packageKey
     * @param MetaData $packageMetaData
     * @return void
     */
    protected function writeComposerManifest($manifestPath, $packageKey, MetaData $packageMetaData = null)
    {
        $manifest = array(
            'name' => $this->getComposerPackageNameFromPackageKey($packageKey)
        );

        if ($packageMetaData !== null) {
            $manifest['type'] = $packageMetaData->getPackageType();
            $manifest['description'] = $packageMetaData->getDescription() ?: 'Add description here';
            if ($packageMetaData->getVersion()) {
                $manifest['version'] = $packageMetaData->getVersion();
            }
            $dependsConstraints = $this->getComposerManifestConstraints(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS, $packageMetaData);
            if ($dependsConstraints !== array()) {
                $manifest['require'] = $dependsConstraints;
            }
            $suggestsConstraints = $this->getComposerManifestConstraints(MetaDataInterface::CONSTRAINT_TYPE_SUGGESTS, $packageMetaData);
            if ($suggestsConstraints !== array()) {
                $manifest['suggest'] = $suggestsConstraints;
            }
            $conflictsConstraints = $this->getComposerManifestConstraints(MetaDataInterface::CONSTRAINT_TYPE_CONFLICTS, $packageMetaData);
            if ($conflictsConstraints !== array()) {
                $manifest['conflict'] = $conflictsConstraints;
            }
        } else {
            $manifest['type'] = 'typo3-flow-package';
            $manifest['description'] = '';
        }
        if (!isset($manifest['require']) || empty($manifest['require'])) {
            $manifest['require'] = array('typo3/flow' => '*');
        }
        $manifest['autoload'] = array('psr-0' => array(str_replace('.', '\\', $packageKey) => 'Classes'));

        if (defined('JSON_PRETTY_PRINT')) {
            file_put_contents(Files::concatenatePaths(array($manifestPath, 'composer.json')), json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            file_put_contents(Files::concatenatePaths(array($manifestPath, 'composer.json')), json_encode($manifest));
        }
    }

    /**
     * Returns the composer manifest constraints ("require", "suggest" or "conflict") from the given package meta data
     *
     * @param string $constraintType one of the MetaDataInterface::CONSTRAINT_TYPE_* constants
     * @param MetaData $packageMetaData
     * @return array in the format array('<ComposerPackageName>' => '*', ...)
     */
    protected function getComposerManifestConstraints($constraintType, MetaData $packageMetaData)
    {
        $composerManifestConstraints = array();
        $constraints = $packageMetaData->getConstraintsByType($constraintType);
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof MetaData\PackageConstraint) {
                continue;
            }
            $composerName = isset($this->packageStatesConfiguration['packages'][$constraint->getValue()]['composerName']) ? $this->packageStatesConfiguration['packages'][$constraint->getValue()]['composerName'] : $this->getComposerPackageNameFromPackageKey($constraint->getValue());
            $composerManifestConstraints[$composerName] = '*';
        }
        return $composerManifestConstraints;
    }

    /**
     * Determines the composer package name ("vendor/foo-bar") from the Flow package key ("Vendor.Foo.Bar")
     *
     * @param string $packageKey
     * @return string
     */
    protected function getComposerPackageNameFromPackageKey($packageKey)
    {
        $nameParts = explode('.', $packageKey);
        $vendor = array_shift($nameParts);
        return strtolower($vendor . '/' . implode('-', $nameParts));
    }

    /**
     * Deactivates a package
     *
     * @param string $packageKey The package to deactivate
     * @return void
     * @throws Exception\ProtectedPackageKeyException if a package is protected and cannot be deactivated
     * @api
     */
    public function deactivatePackage($packageKey)
    {
        if (!$this->isPackageActive($packageKey)) {
            return;
        }

        $package = $this->getPackage($packageKey);
        if ($package->isProtected()) {
            throw new Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
        }

        unset($this->activePackages[$packageKey]);
        $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
        $this->sortAndSavePackageStates();
    }

    /**
     * Activates a package
     *
     * @param string $packageKey The package to activate
     * @return void
     * @api
     */
    public function activatePackage($packageKey)
    {
        if ($this->isPackageActive($packageKey)) {
            return;
        }

        $package = $this->getPackage($packageKey);
        $this->activePackages[$packageKey] = $package;
        $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
        if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['packagePath'])) {
            $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
        }
        if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['classesPath'])) {
            $this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;
        }
        $this->sortAndSavePackageStates();
    }

    /**
     * Freezes a package
     *
     * @param string $packageKey The package to freeze
     * @return void
     * @throws \LogicException
     * @throws Exception\UnknownPackageException
     */
    public function freezePackage($packageKey)
    {
        if (!$this->bootstrap->getContext()->isDevelopment()) {
            throw new \LogicException('Package freezing is only supported in Development context.', 1338810870);
        }

        if (!$this->isPackageActive($packageKey)) {
            throw new Exception\UnknownPackageException('Package "' . $packageKey . '" is not available or active.', 1331715956);
        }
        if ($this->isPackageFrozen($packageKey)) {
            return;
        }

        $this->bootstrap->getObjectManager()->get(ReflectionService::class)->freezePackageReflection($packageKey);

        $this->packageStatesConfiguration['packages'][$packageKey]['frozen'] = true;
        $this->sortAndSavePackageStates();
    }

    /**
     * Tells if a package is frozen
     *
     * @param string $packageKey The package to check
     * @return boolean
     */
    public function isPackageFrozen($packageKey)
    {
        return (
            $this->bootstrap->getContext()->isDevelopment()
            && isset($this->packageStatesConfiguration['packages'][$packageKey]['frozen'])
            && $this->packageStatesConfiguration['packages'][$packageKey]['frozen'] === true
        );
    }

    /**
     * Unfreezes a package
     *
     * @param string $packageKey The package to unfreeze
     * @return void
     */
    public function unfreezePackage($packageKey)
    {
        if (!$this->isPackageFrozen($packageKey)) {
            return;
        }

        $this->bootstrap->getObjectManager()->get(ReflectionService::class)->unfreezePackageReflection($packageKey);

        unset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']);
        $this->sortAndSavePackageStates();
    }

    /**
     * Refreezes a package
     *
     * @param string $packageKey The package to refreeze
     * @return void
     */
    public function refreezePackage($packageKey)
    {
        if (!$this->isPackageFrozen($packageKey)) {
            return;
        }

        $this->bootstrap->getObjectManager()->get(ReflectionService::class)->unfreezePackageReflection($packageKey);
    }

    /**
     * Register a native Flow package
     *
     * @param PackageInterface $package The Package to be registered
     * @param boolean $sortAndSave allows for not saving packagestates when used in loops etc.
     * @return PackageInterface
     * @throws Exception\InvalidPackageStateException
     */
    public function registerPackage(PackageInterface $package, $sortAndSave = true)
    {
        $packageKey = $package->getPackageKey();
        $caseSensitivePackageKey = $this->getCaseSensitivePackageKey($packageKey);
        if ($this->isPackageAvailable($caseSensitivePackageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is already registered as "' . $caseSensitivePackageKey .  '".', 1338996122);
        }

        $this->packages[$packageKey] = $package;
        $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
        $this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = str_replace($package->getPackagePath(), '', $package->getClassesPath());

        if ($sortAndSave === true) {
            $this->sortAndSavePackageStates();
        }

        return $package;
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param PackageInterface $package The package to be unregistered
     * @return void
     * @throws Exception\InvalidPackageStateException
     */
    public function unregisterPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1338996142);
        }
        $this->unregisterPackageByPackageKey($packageKey);
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param string $packageKey Package Key of the package to be unregistered
     * @return void
     */
    protected function unregisterPackageByPackageKey($packageKey)
    {
        unset($this->packages[$packageKey]);
        unset($this->packageKeys[strtolower($packageKey)]);
        unset($this->packageStatesConfiguration['packages'][$packageKey]);
        $this->sortAndSavePackageStates();
    }

    /**
     * Removes a package from registry and deletes it from filesystem
     *
     * @param string $packageKey package to remove
     * @return void
     * @throws Exception\UnknownPackageException if the specified package is not known
     * @throws Exception\ProtectedPackageKeyException if a package is protected and cannot be deleted
     * @throws Exception
     * @api
     */
    public function deletePackage($packageKey)
    {
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\UnknownPackageException('Package "' . $packageKey . '" is not available and cannot be removed.', 1166543253);
        }

        $package = $this->getPackage($packageKey);
        if ($package->isProtected()) {
            throw new Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1220722120);
        }

        if ($this->isPackageActive($packageKey)) {
            $this->deactivatePackage($packageKey);
        }

        $packagePath = $package->getPackagePath();
        try {
            Files::removeDirectoryRecursively($packagePath);
        } catch (UtilityException $exception) {
            throw new Exception('Please check file permissions. The directory "' . $packagePath . '" for package "' . $packageKey . '" could not be removed.', 1301491089, $exception);
        }

        $this->unregisterPackage($package);
    }

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     *
     * @return void
     */
    protected function loadPackageStates()
    {
        $this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
        if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 5) {
            $this->packageStatesConfiguration = array();
        }
        if ($this->packageStatesConfiguration === array() || !$this->bootstrap->getContext()->isProduction()) {
            $this->scanAvailablePackages();
        } else {
            $this->registerPackagesFromConfiguration();
        }
    }

    /**
     * Scans all directories in the packages directories for available packages.
     * For each package a Package object is created and stored in $this->packages.
     *
     * @return void
     */
    protected function scanAvailablePackages()
    {
        $previousPackageStatesConfiguration = $this->packageStatesConfiguration;

        if (isset($this->packageStatesConfiguration['packages'])) {
            foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $configuration) {
                if (!file_exists($this->packagesBasePath . $configuration['packagePath'])) {
                    unset($this->packageStatesConfiguration['packages'][$packageKey]);
                }
            }
        } else {
            $this->packageStatesConfiguration['packages'] = array();
        }

        $packagePaths = array();
        foreach (new \DirectoryIterator($this->packagesBasePath) as $parentFileInfo) {
            $parentFilename = $parentFileInfo->getFilename();
            if ($parentFilename[0] !== '.' && $parentFileInfo->isDir()) {
                $packagePaths = array_merge($packagePaths, $this->scanPackagesInPath($parentFileInfo->getPathName()));
            }
        }

        /**
         * @todo similar functionality in registerPackage - should be refactored
         */
        foreach ($packagePaths as $packagePath => $composerManifestPath) {
            try {
                $composerManifest = self::getComposerManifest($composerManifestPath);
                $packageKey = PackageFactory::getPackageKeyFromManifest($composerManifest, $packagePath, $this->packagesBasePath);
                $this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
                $this->packageStatesConfiguration['packages'][$packageKey]['manifestPath'] = substr($composerManifestPath, strlen($packagePath)) ?: '';
                $this->packageStatesConfiguration['packages'][$packageKey]['composerName'] = $composerManifest->name;
            } catch (MissingPackageManifestException $exception) {
                $relativePackagePath = substr($packagePath, strlen($this->packagesBasePath));
                $packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
            }
            if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['state'])) {
                /**
                 * @todo doesn't work, settings not available at this time
                 */
                if (is_array($this->settings['package']['inactiveByDefault']) && in_array($packageKey, $this->settings['package']['inactiveByDefault'], true)) {
                    $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
                } else {
                    $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
                }
            }

            $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $packagePath);

            // Change this to read the target from Composer or any other source
            $this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;
        }

        $this->registerPackagesFromConfiguration();
        if ($this->packageStatesConfiguration != $previousPackageStatesConfiguration) {
            $this->sortAndSavePackageStates();
        }
    }

    /**
     * Looks for composer.json in the given path and returns a path or NULL.
     *
     * @param string $packagePath
     * @return array
     */
    protected function findComposerManifestPaths($packagePath)
    {
        $manifestPaths = array();
        if (file_exists($packagePath . '/composer.json')) {
            $manifestPaths[] = $packagePath . '/';
        } else {
            $jsonPathsAndFilenames = Files::readDirectoryRecursively($packagePath, '.json');
            asort($jsonPathsAndFilenames);
            while (list($unusedKey, $jsonPathAndFilename) = each($jsonPathsAndFilenames)) {
                if (basename($jsonPathAndFilename) === 'composer.json') {
                    $manifestPath = dirname($jsonPathAndFilename) . '/';
                    $manifestPaths[] = $manifestPath;
                    $isNotSubPathOfManifestPath = function ($otherPath) use ($manifestPath) {
                        return strpos($otherPath, $manifestPath) !== 0;
                    };
                    $jsonPathsAndFilenames = array_filter($jsonPathsAndFilenames, $isNotSubPathOfManifestPath);
                }
            }
        }

        return $manifestPaths;
    }

    /**
     * Scans all sub directories of the specified directory and collects the package keys of packages it finds.
     *
     * The return of the array is to make this method usable in array_merge.
     *
     * @param string $startPath
     * @param array $collectedPackagePaths
     * @return array
     */
    protected function scanPackagesInPath($startPath, array &$collectedPackagePaths = array())
    {
        foreach (new \DirectoryIterator($startPath) as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            if ($filename[0] !== '.') {
                $currentPath = Files::getUnixStylePath($fileInfo->getPathName());
                $composerManifestPaths = $this->findComposerManifestPaths($currentPath);
                foreach ($composerManifestPaths as $composerManifestPath) {
                    $targetDirectory = rtrim(self::getComposerManifest($composerManifestPath, 'target-dir'), '/');
                    $packagePath = $targetDirectory ? substr(rtrim($composerManifestPath, '/'), 0, -strlen((string)$targetDirectory)) : $composerManifestPath;
                    $collectedPackagePaths[$packagePath] = $composerManifestPath;
                }
            }
        }
        return $collectedPackagePaths;
    }

    /**
     * Returns contents of Composer manifest - or part there of.
     *
     * @param string $manifestPath
     * @param string $key Optional. Only return the part of the manifest indexed by 'key'
     * @param object $composerManifest Optional. Manifest to use instead of reading it from file
     * @return mixed
     * @throws MissingPackageManifestException
     * @see json_decode for return values
     */
    public static function getComposerManifest($manifestPath, $key = null, $composerManifest = null)
    {
        if ($composerManifest === null) {
            $composerManifest = self::readComposerManifest($manifestPath);
        }

        if ($key !== null) {
            if (isset($composerManifest->{$key})) {
                $value = $composerManifest->{$key};
            } else {
                $value = null;
            }
        } else {
            $value = $composerManifest;
        }
        return $value;
    }

    /**
     * Read the content of the composer.lock
     *
     * @return array
     */
    public static function readComposerLock()
    {
        if (self::$composerLockCache === null) {
            if (!file_exists(FLOW_PATH_ROOT . 'composer.lock')) {
                return array();
            }
            $json = file_get_contents(FLOW_PATH_ROOT . 'composer.lock');
            $composerLock = json_decode($json, true);
            $composerPackageVersions = isset($composerLock['packages']) ? $composerLock['packages'] : array();
            $composerPackageDevVersions = isset($composerLock['packages-dev']) ? $composerLock['packages-dev'] : array();
            self::$composerLockCache = array_merge($composerPackageVersions, $composerPackageDevVersions);
        }

        return self::$composerLockCache;
    }

    /**
     * Read the content of composer.json in the given path
     *
     * @param string $manifestPath
     * @return \stdClass
     * @throws MissingPackageManifestException
     */
    protected static function readComposerManifest($manifestPath)
    {
        if (isset(self::$composerManifestData[$manifestPath])) {
            return self::$composerManifestData[$manifestPath];
        }

        if (!file_exists($manifestPath . 'composer.json')) {
            throw new MissingPackageManifestException(sprintf('No composer manifest file found at "%s/composer.json".', $manifestPath), 1349868540);
        }
        $json = file_get_contents($manifestPath . 'composer.json');
        $composerManifest = json_decode($json);
        $composerManifest->version = self::getPackageVersion($composerManifest->name);

        self::$composerManifestData[$manifestPath] = $composerManifest;
        return $composerManifest;
    }

    /**
     * Get the package version of the given package
     * Return normalized package version.
     *
     * @param string $packageName
     * @return string
     * @see https://getcomposer.org/doc/04-schema.md#version
     */
    protected static function getPackageVersion($packageName)
    {
        foreach (self::readComposerLock() as $packageState) {
            if (!isset($packageState['name'])) {
                continue;
            }
            if ($packageState['name'] === $packageName) {
                return preg_replace('/^v([0-9])/', '$1', $packageState['version'], 1);
            }
        }

        return '';
    }

    /**
     * Requires and registers all packages which were defined in packageStatesConfiguration
     *
     * @return void
     * @throws Exception\CorruptPackageException
     */
    protected function registerPackagesFromConfiguration()
    {
        foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {
            $packagePath = isset($stateConfiguration['packagePath']) ? $stateConfiguration['packagePath'] : null;
            $classesPath = isset($stateConfiguration['classesPath']) ? $stateConfiguration['classesPath'] : null;
            $manifestPath = isset($stateConfiguration['manifestPath']) ? $stateConfiguration['manifestPath'] : null;

            try {
                $package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageKey, $classesPath, $manifestPath);
            } catch (Exception\InvalidPackagePathException $exception) {
                $this->unregisterPackageByPackageKey($packageKey);
                $this->systemLogger->log('Package ' . $packageKey . ' could not be loaded, it has been unregistered. Error description: "' . $exception->getMessage() . '" (' . $exception->getCode() . ')', LOG_WARNING);
                continue;
            }

            $this->registerPackage($package, false);

            if (!$this->packages[$packageKey] instanceof PackageInterface) {
                throw new Exception\CorruptPackageException(sprintf('The package class in package "%s" does not implement PackageInterface.', $packageKey), 1300782487);
            }

            $this->packageKeys[strtolower($packageKey)] = $packageKey;
            if ($stateConfiguration['state'] === 'active') {
                $this->activePackages[$packageKey] = $this->packages[$packageKey];
            }
        }
    }

    /**
     * Saves the current content of $this->packageStatesConfiguration to the
     * PackageStates.php file.
     *
     * @return void
     * @throws Exception\PackageStatesFileNotWritableException
     */
    protected function sortAndSavePackageStates()
    {
        $this->sortAvailablePackagesByDependencies();

        $this->packageStatesConfiguration['version'] = 5;

        $fileDescription = "# PackageStates.php\n\n";
        $fileDescription .= "# This file is maintained by Flow's package management. Although you can edit it\n";
        $fileDescription .= "# manually, you should rather use the command line commands for maintaining packages.\n";
        $fileDescription .= "# You'll find detailed information about the typo3.flow:package:* commands in their\n";
        $fileDescription .= "# respective help screens.\n\n";
        $fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
        $fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

        $packageStatesCode = "<?php\n$fileDescription\nreturn " . var_export($this->packageStatesConfiguration, true) . ';';
        $result = @file_put_contents($this->packageStatesPathAndFilename, $packageStatesCode);
        if ($result === false) {
            throw new Exception\PackageStatesFileNotWritableException(sprintf('Flow could not update the list of installed packages because the file %s is not writable. Please, check the file system permissions and make sure that the web server can write to it.', $this->packageStatesPathAndFilename), 1382449759);
        }
        OpcodeCacheHelper::clearAllActive($this->packageStatesPathAndFilename);

        $this->emitPackageStatesUpdated();
    }

    /**
     * Orders all packages by comparing their dependencies. By this, the packages
     * and package configurations arrays holds all packages in the correct
     * initialization order.
     *
     * @return void
     */
    protected function sortAvailablePackagesByDependencies()
    {
        $sortedPackages = array();
        $unsortedPackages = array_fill_keys(array_keys($this->packages), 0);

        while (!empty($unsortedPackages)) {
            reset($unsortedPackages);
            $this->sortPackagesByDependencies(key($unsortedPackages), $sortedPackages, $unsortedPackages);
        }

        $this->packages = $sortedPackages;

        $packageStatesConfiguration = array();
        foreach ($sortedPackages as $packageKey => $package) {
            $packageStatesConfiguration[$packageKey] = $this->packageStatesConfiguration['packages'][$packageKey];
        }
        $this->packageStatesConfiguration['packages'] = $packageStatesConfiguration;
    }

    /**
     * Recursively sort dependencies of a package. This is a depth-first approach that recursively
     * adds all dependent packages to the sorted list before adding the given package. Visited
     * packages are flagged to break up cyclic dependencies.
     *
     * @param string $packageKey Package key to process
     * @param array $sortedPackages Array to sort packages into
     * @param array $unsortedPackages Array with state information of still unsorted packages
     */
    protected function sortPackagesByDependencies($packageKey, array &$sortedPackages, array &$unsortedPackages)
    {
        if ($unsortedPackages[$packageKey] === 0) {
            $package = $this->packages[$packageKey];
            $unsortedPackages[$packageKey] = 1;
            $dependentPackageConstraints = $package->getPackageMetaData()->getConstraintsByType(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS);
            foreach ($dependentPackageConstraints as $constraint) {
                if ($constraint instanceof MetaData\PackageConstraint) {
                    $dependentPackageKey = $constraint->getValue();
                    if (isset($unsortedPackages[$dependentPackageKey])) {
                        $this->sortPackagesByDependencies($dependentPackageKey, $sortedPackages, $unsortedPackages);
                    }
                }
            }
            unset($unsortedPackages[$packageKey]);
            $sortedPackages[$packageKey] = $package;
        }
    }

    /**
     * Emits a signal when package states have been changed (e.g. when a package was created or activated)
     *
     * The advice is not proxyable, so the signal is dispatched manually here.
     *
     * @return void
     * @Flow\Signal
     */
    protected function emitPackageStatesUpdated()
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->bootstrap->getEarlyInstance(Dispatcher::class);
        }

        $this->dispatcher->dispatch(PackageManager::class, 'packageStatesUpdated');
    }
}

<?php
namespace Neos\Flow\Package;

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
use Neos\Flow\Composer\Exception\InvalidConfigurationException;
use Neos\Flow\Composer\Exception\MissingPackageManifestException;
use Neos\Flow\Composer\ComposerUtility as ComposerUtility;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Utility\Exception as UtilityException;
use Neos\Utility\Files;
use Neos\Utility\OpcodeCacheHelper;

/**
 * The default Flow Package Manager
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PackageManager implements PackageManagerInterface
{
    /**
     * The current format version for PackageStates.php files
     */
    const PACKAGESTATE_FORMAT_VERSION = 6;

    /**
     * The folder name for inactive packages.
     */
    const INACTIVE_PACKAGES_FOLDER = 'Inactive';

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
     * Array of available packages, indexed by package key (case sensitive)
     *
     * @var array
     */
    protected $packages = [];

    /**
     * A translation table between lower cased and upper camel cased package keys
     *
     * @var array
     */
    protected $packageKeys = [];

    /**
     * A map between ComposerName and PackageKey, only available when scanAvailablePackages is run
     *
     * @var array
     */
    protected $composerNameToPackageKeyMap = [];

    /**
     * List of active packages as package key => package object
     *
     * @var array
     */
    protected $activePackages = [];

    /**
     * Absolute path leading to the various package directories
     *
     * @var string
     */
    protected $packagesBasePath = FLOW_PATH_PACKAGES;

    /**
     * @var string
     */
    protected $packageStatesPathAndFilename;

    /**
     * Package states configuration as stored in the PackageStates.php file
     *
     * @var array
     */
    protected $packageStatesConfiguration = [];

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param string $packageStatesPathAndFilename
     */
    public function __construct($packageStatesPathAndFilename = '')
    {
        $this->packageFactory = new PackageFactory();
        $this->packageStatesPathAndFilename = $packageStatesPathAndFilename ?: FLOW_PATH_CONFIGURATION . 'PackageStates.php';
    }

    /**
     * Initializes the package manager
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function initialize(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->packageStatesConfiguration = $this->getCurrentPackageStates();
        $this->activePackages = [];
        $this->registerPackagesFromConfiguration($this->packageStatesConfiguration);
        /** @var PackageInterface $package */

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
        return ($this->getCaseSensitivePackageKey($packageKey) !== false);
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
        $frozenPackages = [];
        if ($this->bootstrap->getContext()->isDevelopment()) {
            /** @var PackageInterface $package */
            foreach ($this->packages as $packageKey => $package) {
                if (isset($this->packageStatesConfiguration['packages'][$package->getComposerName()]['frozen']) &&
                    $this->packageStatesConfiguration['packages'][$package->getComposerName()]['frozen'] === true
                ) {
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
        $filteredPackages = [];
        /** @var $package Package */
        foreach ($packages as $package) {
            $packagePath = substr($package->getPackagePath(), strlen($this->packagesBasePath));
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
        $filteredPackages = [];
        /** @var $package Package */
        foreach ($packages as $package) {
            if ($package->getComposerManifest('type') === $packageType) {
                $filteredPackages[$package->getPackageKey()] = $package;
            }
        }

        return $filteredPackages;
    }

    /**
     * Create a package, given the package key
     *
     * @param string $packageKey The package key of the new package
     * @param array $manifest A composer manifest as associative array.
     * @param string $packagesPath If specified, the package will be created in this path, otherwise the default "Application" directory is used
     * @return PackageInterface The newly created package
     *
     * @throws Exception\PackageKeyAlreadyExistsException
     * @throws Exception\InvalidPackageKeyException
     * @throws Exception\PackageKeyAlreadyExistsException
     * @api
     */
    public function createPackage($packageKey, array $manifest = [], $packagesPath = null)
    {
        if (!$this->isPackageKeyValid($packageKey)) {
            throw new Exception\InvalidPackageKeyException('The package key "' . $packageKey . '" is invalid', 1220722210);
        }
        if ($this->isPackageAvailable($packageKey)) {
            throw new Exception\PackageKeyAlreadyExistsException('The package key "' . $packageKey . '" already exists', 1220722873);
        }
        if (!isset($manifest['type'])) {
            $manifest['type'] = PackageInterface::DEFAULT_COMPOSER_TYPE;
        }

        if ($packagesPath === null) {
            $packagesPath = 'Application';
            if (is_array($this->settings['package']['packagesPathByType']) && isset($this->settings['package']['packagesPathByType'][$manifest['type']])) {
                $packagesPath = $this->settings['package']['packagesPathByType'][$manifest['type']];
            }

            $packagesPath = Files::getUnixStylePath(Files::concatenatePaths([$this->packagesBasePath, $packagesPath]));
        }

        $packagePath = Files::concatenatePaths([$packagesPath, $packageKey]) . '/';
        Files::createDirectoryRecursively($packagePath);

        foreach (
            [
                PackageInterface::DIRECTORY_CLASSES,
                PackageInterface::DIRECTORY_CONFIGURATION,
                PackageInterface::DIRECTORY_RESOURCES,
                PackageInterface::DIRECTORY_TESTS_UNIT,
                PackageInterface::DIRECTORY_TESTS_FUNCTIONAL,
            ] as $path) {
            Files::createDirectoryRecursively(Files::concatenatePaths([$packagePath, $path]));
        }

        $manifest = ComposerUtility::writeComposerManifest($packagePath, $packageKey, $manifest);

        $refreshedPackageStatesConfiguration = $this->rescanPackages(false);
        $this->packageStatesConfiguration = $refreshedPackageStatesConfiguration;
        $this->registerPackageFromStateConfiguration($manifest['name'], $this->packageStatesConfiguration['packages'][$manifest['name']]);

        return $this->packages[$packageKey];
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
        $composerName = $package->getComposerName();

        $this->movePackageToDeactivatedPackages($package->getPackagePath());
        $this->packageStatesConfiguration['packages'][$composerName]['state'] = self::PACKAGE_STATE_INACTIVE;
        $this->packageStatesConfiguration['packages'][$composerName]['packagePath'] = $this->buildInactivePackageRelativePath($package->getPackagePath());
        $this->registerPackageFromStateConfiguration($composerName, $this->packageStatesConfiguration['packages'][$composerName]);
        $this->savePackageStates($this->packageStatesConfiguration);
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
        $composerName = $package->getComposerName();

        $this->movePackageToActivatedPackages($package->getPackagePath());
        $this->packageStatesConfiguration['packages'][$composerName]['state'] = self::PACKAGE_STATE_ACTIVE;
        $this->packageStatesConfiguration['packages'][$composerName]['packagePath'] = $this->buildActivePackageRelativePath($package->getPackagePath());
        $this->registerPackageFromStateConfiguration($composerName, $this->packageStatesConfiguration['packages'][$composerName]);
        $this->savePackageStates($this->packageStatesConfiguration);
    }

    /**
     * Build the relative path to store a deactivated package based on the package.
     *
     * @param string $packagePath absolute path to the package
     * @return string
     */
    protected function buildInactivePackageRelativePath($packagePath)
    {
        return Files::getNormalizedPath(Files::concatenatePaths([self::INACTIVE_PACKAGES_FOLDER, str_replace($this->packagesBasePath, '', $packagePath)]));
    }

    /**
     * Build the relative path to store an active package from the given inactive path
     *
     * @param string $inactivePackagePath
     * @return string
     */
    protected function buildActivePackageRelativePath($inactivePackagePath)
    {
        $inactivePackagesBasePath = Files::getNormalizedPath(Files::concatenatePaths([$this->packagesBasePath, self::INACTIVE_PACKAGES_FOLDER]));
        return Files::getNormalizedPath(str_replace($inactivePackagesBasePath, '', $inactivePackagePath));
    }

    /**
     * Moves a package from the regular package path to the inactive packages directory.
     *
     * @param string $packagePath absolute path to the package
     * @return void
     */
    protected function movePackageToDeactivatedPackages($packagePath)
    {
        $inactivePackagePath = Files::getNormalizedPath(Files::concatenatePaths([
            $this->packagesBasePath,
            $this->buildInactivePackageRelativePath($packagePath)
        ]));
        $this->movePackage($packagePath, $inactivePackagePath);
    }

    /**
     * Moves a package from the given inactive package path to the active package path.
     *
     * @param string $inactivePackagePath
     * @return void
     */
    protected function movePackageToActivatedPackages($inactivePackagePath)
    {
        $activePackagePath = Files::getNormalizedPath(Files::concatenatePaths([
            $this->packagesBasePath,
            $this->buildActivePackageRelativePath($inactivePackagePath)
        ]));
        $this->movePackage($inactivePackagePath, $activePackagePath);
    }

    /**
     * Moves a package from one path to another.
     *
     * @param string $fromAbsolutePath
     * @param string $toAbsolutePath
     * @return void
     */
    protected function movePackage($fromAbsolutePath, $toAbsolutePath)
    {
        Files::createDirectoryRecursively($toAbsolutePath);
        Files::copyDirectoryRecursively($fromAbsolutePath, $toAbsolutePath, false, true);
        Files::removeDirectoryRecursively($fromAbsolutePath);
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

        $package = $this->packages[$packageKey];
        $this->bootstrap->getObjectManager()->get(ReflectionService::class)->freezePackageReflection($packageKey);

        $this->packageStatesConfiguration['packages'][$package->getComposerName()]['frozen'] = true;
        $this->savePackageStates($this->packageStatesConfiguration);
    }

    /**
     * Tells if a package is frozen
     *
     * @param string $packageKey The package to check
     * @return boolean
     */
    public function isPackageFrozen($packageKey)
    {
        if (!isset($this->packages[$packageKey])) {
            return false;
        }
        $composerName = $this->packages[$packageKey]->getComposerName();

        return (
            $this->bootstrap->getContext()->isDevelopment()
            && isset($this->packageStatesConfiguration['packages'][$composerName]['frozen'])
            && $this->packageStatesConfiguration['packages'][$composerName]['frozen'] === true
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
        if (!isset($this->packages[$packageKey])) {
            return;
        }
        $composerName = $this->packages[$packageKey]->getComposerName();

        $this->bootstrap->getObjectManager()->get(ReflectionService::class)->unfreezePackageReflection($packageKey);

        unset($this->packageStatesConfiguration['packages'][$composerName]['frozen']);
        $this->savePackageStates($this->packageStatesConfiguration);
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

        $packagePath = $package->getPackagePath();
        if ($this->isPackageActive($packageKey)) {
            $this->deactivatePackage($packageKey);
            $packagePath = Files::concatenatePaths([$this->packagesBasePath, $this->buildInactivePackageRelativePath($packagePath)]);
        }

        $this->unregisterPackage($package);

        try {
            Files::removeDirectoryRecursively($packagePath);
        } catch (UtilityException $exception) {
            throw new Exception('Please check file permissions. The directory "' . $packagePath . '" for package "' . $packageKey . '" could not be removed.', 1301491089, $exception);
        }
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param PackageInterface $package The package to be unregistered
     * @return void
     * @throws Exception\InvalidPackageStateException
     */
    protected function unregisterPackage(PackageInterface $package)
    {
        $packageKey = $package->getPackageKey();
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1338996142);
        }

        if (!isset($this->packages[$packageKey])) {
            return;
        }
        $composerName = $package->getComposerName();

        unset($this->packages[$packageKey], $this->packageKeys[strtolower($packageKey)], $this->packageStatesConfiguration['packages'][$composerName]);
        $this->sortAndSavePackageStates($this->packageStatesConfiguration);
    }

    /**
     * Rescans available packages, order and write a new PackageStates file.
     *
     * @param boolean $reloadPackageStates Should the package states be loaded before scanning or use the current configuration
     * @return array The found and sorted package states.
     * @api
     * TODO: Add to Interface with Flow 4.0
     */
    public function rescanPackages($reloadPackageStates = true)
    {
        $loadedPackageStates = $this->packageStatesConfiguration;
        if ($reloadPackageStates) {
            $loadedPackageStates = $this->loadPackageStates();
        }
        $loadedPackageStates = $this->scanAvailablePackages($loadedPackageStates);
        $loadedPackageStates = $this->sortAndSavePackageStates($loadedPackageStates);

        return $loadedPackageStates;
    }

    /**
     * Loads the states of available packages from the PackageStates.php file and
     * initialises a package scan if the file was not found or the configuration format
     * was not current.
     *
     * @return array
     */
    protected function getCurrentPackageStates()
    {
        $savePackageStates = false;
        $loadedPackageStates = $this->loadPackageStates();
        if (
            empty($loadedPackageStates)
            || !isset($loadedPackageStates['version'])
            || $loadedPackageStates['version'] < self::PACKAGESTATE_FORMAT_VERSION
        ) {
            $loadedPackageStates = $this->scanAvailablePackages($loadedPackageStates);
            $savePackageStates = true;
        }

        if ($savePackageStates) {
            $loadedPackageStates = $this->sortAndSavePackageStates($loadedPackageStates);
        }

        return $loadedPackageStates;
    }

    /**
     * Load the current package states
     *
     * @return array
     */
    protected function loadPackageStates()
    {
        return (is_file($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : []);
    }

    /**
     * Scans all directories in the packages directories for available packages.
     * For each package a Package object is created and stored in $this->packages.
     *
     * @param array $previousPackageStatesConfiguration Existing package state configuration
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function scanAvailablePackages($previousPackageStatesConfiguration)
    {
        $recoveredStateByPackage = $this->recoverStateFromConfiguration($previousPackageStatesConfiguration);
        $newPackageStatesConfiguration = ['packages' => []];

        $inactivePackages = [];
        try {
            $globalComposerManifest = ComposerUtility::getComposerManifest(FLOW_PATH_ROOT);
            $inactivePackages = (isset($globalComposerManifest['extra']['neos']['default-disabled-packages']) && is_array($globalComposerManifest['extra']['neos']['default-disabled-packages'])) ? $globalComposerManifest['extra']['neos']['default-disabled-packages'] : [];
        } catch (MissingPackageManifestException $exception) {
            // TODO: We should probably throw an exception here and warn about the missing composer.json, but on production machines it might be missing...
        }

        foreach ($this->findComposerPackagesInPath($this->packagesBasePath) as $packagePath) {
            $composerManifest = ComposerUtility::getComposerManifest($packagePath);
            if (!isset($composerManifest['name'])) {
                throw new InvalidConfigurationException(sprintf('A package composer.json was found at "%s" that contained no "name".', $packagePath), 1445933572);
            }
            $packageKey = $this->getPackageKeyFromManifest($composerManifest, $packagePath);
            $this->composerNameToPackageKeyMap[strtolower($composerManifest['name'])] = $packageKey;

            $state = in_array($composerManifest['name'], $inactivePackages, true) ? self::PACKAGE_STATE_INACTIVE : self::PACKAGE_STATE_ACTIVE;

            if (isset($recoveredStateByPackage[$composerManifest['name']])) {
                $state = $recoveredStateByPackage[$composerManifest['name']];
            }

            $packageInActivePackagesFolder = true;
            if (strpos($packagePath, Files::concatenatePaths([$this->packagesBasePath, self::INACTIVE_PACKAGES_FOLDER])) === 0) {
                $packageInActivePackagesFolder = false;
                $state = self::PACKAGE_STATE_INACTIVE;
            }

            $packageConfiguration = $this->preparePackageStateConfiguration($packageKey, $packagePath, $composerManifest, $state);
            $newPackageStatesConfiguration['packages'][$composerManifest['name']] = $packageConfiguration;

            if ($state === self::PACKAGE_STATE_INACTIVE && $packageInActivePackagesFolder && is_dir($packagePath)) {
                $newPackageStatesConfiguration['packages'][$composerManifest['name']]['packagePath'] = $this->buildInactivePackageRelativePath($packagePath);
                $this->movePackageToDeactivatedPackages($packagePath);
            }
        }

        return $newPackageStatesConfiguration;
    }

    /**
     * Recursively traverses directories from the given starting points and returns all folder paths that contain a composer.json and
     * which does NOT have the key "extra.neos.is-merged-repository" set, as that indicates a composer package that joins several "real" packages together.
     * In case a "is-merged-repository" is found the traversal continues inside.
     *
     * @param string $startingDirectory
     * @return \Generator
     */
    protected function findComposerPackagesInPath($startingDirectory)
    {
        $directories = [$startingDirectory];
        while ($directories !== []) {
            $currentDirectory = array_pop($directories);
            if ($handle = opendir($currentDirectory)) {
                while (false !== ($filename = readdir($handle))) {
                    if ($filename[0] === '.') {
                        continue;
                    }
                    $pathAndFilename = $currentDirectory . $filename;
                    if (is_dir($pathAndFilename)) {
                        $potentialPackageDirectory = $pathAndFilename . '/';
                        if (is_file($potentialPackageDirectory . 'composer.json')) {
                            $composerManifest = ComposerUtility::getComposerManifest($potentialPackageDirectory);
                            // TODO: Maybe get rid of magic string "neos-package-collection" by fetching collection package types from outside.
                            if (isset($composerManifest['type']) && $composerManifest['type'] === 'neos-package-collection') {
                                $directories[] = $potentialPackageDirectory;
                                continue;
                            }
                            yield $potentialPackageDirectory;
                        } else {
                            $directories[] = $potentialPackageDirectory;
                        }
                    }
                }
                closedir($handle);
            }
        }
    }

    /**
     * @param string $packageKey
     * @param string $packagePath
     * @param array $composerManifest
     * @param string $state
     * @return array
     */
    protected function preparePackageStateConfiguration($packageKey, $packagePath, $composerManifest, $state = self::PACKAGE_STATE_ACTIVE)
    {
        $autoload = isset($composerManifest['autoload']) ? $composerManifest['autoload'] : [];

        return [
            'state' => $state,
            'packageKey' => $packageKey,
            'packagePath' => str_replace($this->packagesBasePath, '', $packagePath),
            'composerName' => $composerManifest['name'],
            'autoloadConfiguration' => $autoload,
            'packageClassInformation' => $this->packageFactory->detectFlowPackageFilePath($packageKey, $packagePath)
        ];
    }

    /**
     * Get the package version of the given package
     * Return normalized package version.
     *
     * @param string $composerName
     * @return string
     * @see https://getcomposer.org/doc/04-schema.md#version
     */
    public static function getPackageVersion($composerName)
    {
        foreach (ComposerUtility::readComposerLock() as $composerLockData) {
            if (!isset($composerLockData['name'])) {
                continue;
            }
            if ($composerLockData['name'] === $composerName) {
                return preg_replace('/^v([0-9])/', '$1', $composerLockData['version'], 1);
            }
        }

        return '';
    }

    /**
     * Requires and registers all packages which were defined in packageStatesConfiguration
     *
     * @param array $packageStatesConfiguration
     * @throws Exception\CorruptPackageException
     */
    protected function registerPackagesFromConfiguration($packageStatesConfiguration)
    {
        foreach ($packageStatesConfiguration['packages'] as $composerName => $packageStateConfiguration) {
            $this->registerPackageFromStateConfiguration($composerName, $packageStateConfiguration);
        }
    }

    /**
     * Registers a package under the given composer name with the configuration.
     * This uses the PackageFactory to create the Package instance and sets it
     * to all relevant data arrays.
     *
     * @param string $composerName
     * @param array $packageStateConfiguration
     * @return void
     */
    protected function registerPackageFromStateConfiguration($composerName, $packageStateConfiguration)
    {
        $packagePath = isset($packageStateConfiguration['packagePath']) ? $packageStateConfiguration['packagePath'] : null;
        $packageClassInformation = isset($packageStateConfiguration['packageClassInformation']) ? $packageStateConfiguration['packageClassInformation'] : null;
        $package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageStateConfiguration['packageKey'], $composerName, $packageStateConfiguration['autoloadConfiguration'], $packageClassInformation);
        $this->packageKeys[strtolower($package->getPackageKey())] = $package->getPackageKey();
        $this->packages[$package->getPackageKey()] = $package;
        if (isset($this->activePackages[$package->getPackageKey()])) {
            unset($this->activePackages[$package->getPackageKey()]);
        }
        if ((isset($packageStateConfiguration['state']) && $packageStateConfiguration['state'] === self::PACKAGE_STATE_ACTIVE) || $package->isProtected()) {
            $this->activePackages[$package->getPackageKey()] = $package;
        }
    }

    /**
     * Takes the given packageStatesConfiguration, sorts it by dependencies, saves it and returns
     * the ordered list
     *
     * @param array $packageStates
     * @return array
     */
    protected function sortAndSavePackageStates(array $packageStates)
    {
        $orderedPackageStates = $this->sortAvailablePackagesByDependencies($packageStates);
        $this->savePackageStates($orderedPackageStates);

        return $orderedPackageStates;
    }

    /**
     * Save the given (ordered) array of package states data
     *
     * @param array $orderedPackageStates
     * @throws Exception\PackageStatesFileNotWritableException
     */
    protected function savePackageStates(array $orderedPackageStates)
    {
        $orderedPackageStates['version'] = static::PACKAGESTATE_FORMAT_VERSION;

        $fileDescription = "# PackageStates.php\n\n";
        $fileDescription .= "# This file is maintained by Flow's package management. You shouldn't edit it manually\n";
        $fileDescription .= "# manually, you should rather use the command line commands for maintaining packages.\n";
        $fileDescription .= "# You'll find detailed information about the neos.flow:package:* commands in their\n";
        $fileDescription .= "# respective help screens.\n\n";
        $fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
        $fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

        $packageStatesCode = "<?php\n" . $fileDescription . "\nreturn " . var_export($orderedPackageStates, true) . ';';

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
     * @param array $packageStates The unordered package states
     * @return array ordered package states.
     */
    protected function sortAvailablePackagesByDependencies(array $packageStates)
    {
        $packageOrderResolver = new PackageOrderResolver($packageStates['packages'], $this->collectPackageManifestData($packageStates));
        $packageStates['packages'] = $packageOrderResolver->sort();

        return $packageStates;
    }

    /**
     * Collects the manifest data for all packages in the given package states array
     *
     * @param array $packageStates
     * @return array
     */
    protected function collectPackageManifestData(array $packageStates)
    {
        return array_map(function ($packageState) {
            return ComposerUtility::getComposerManifest(Files::getNormalizedPath(Files::concatenatePaths([$this->packagesBasePath, $packageState['packagePath']])));
        }, $packageStates['packages']);
    }

    /**
     * Recover previous package state from given packageStatesConfiguration to be used
     * after rescanning packages.
     *
     * @param array $packageStatesConfiguration
     * @return array
     */
    protected function recoverStateFromConfiguration($packageStatesConfiguration)
    {
        $packageStateByComposerName = [];
        if (isset($packageStatesConfiguration['packages']) && is_array($packageStatesConfiguration['packages'])) {
            foreach ($packageStatesConfiguration['packages'] as $key => $package) {
                if (isset($package['state'])) {
                    if (isset($package['packageKey']) && $this->isPackageKeyValid($package['packageKey']) && isset($package['composerName'])) {
                        $packageStateByComposerName[$package['composerName']] = $package['state'];
                    } else {
                        $packageStateByComposerName[$key] = $package['state'];
                    }
                }
            }
        }

        return $packageStateByComposerName;
    }

    /**
     * Returns the correctly cased version of the given package key or FALSE
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
        if ($this->composerNameToPackageKeyMap === []) {
            foreach ($this->packageStatesConfiguration['packages'] as $packageStateConfiguration) {
                $this->composerNameToPackageKeyMap[$packageStateConfiguration['composerName']] = $packageStateConfiguration['packageKey'];
            }
        }

        $lowercasedComposerName = strtolower($composerName);
        if (!isset($this->composerNameToPackageKeyMap[$lowercasedComposerName])) {
            throw new Exception\InvalidPackageStateException('Could not find package with composer name "' . $lowercasedComposerName . '" in PackageStates configuration.', 1352320649);
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
     * Resolves package key from Composer manifest
     *
     * If it is a Flow package the name of the containing directory will be used.
     *
     * Else if the composer name of the package matches the first part of the lowercased namespace of the package, the mixed
     * case version of the composer name / namespace will be used, with backslashes replaced by dots.
     *
     * Else the composer name will be used with the slash replaced by a dot
     *
     * @param array $manifest
     * @param string $packagePath
     * @return string
     */
    protected function getPackageKeyFromManifest(array $manifest, $packagePath)
    {
        if (isset($manifest['extra']['neos']['package-key']) && $this->isPackageKeyValid($manifest['extra']['neos']['package-key'])) {
            return $manifest['extra']['neos']['package-key'];
        }

        $composerName = $manifest['name'];
        $autoloadNamespace = null;
        $type = null;
        if (isset($manifest['autoload']['psr-0']) && is_array($manifest['autoload']['psr-0'])) {
            $namespaces = array_keys($manifest['autoload']['psr-0']);
            $autoloadNamespace = reset($namespaces);
        }

        if (isset($manifest['type'])) {
            $type = $manifest['type'];
        }

        return $this->derivePackageKey($composerName, $type, $packagePath, $autoloadNamespace);
    }

    /**
     * Derive a flow package key from the given information.
     * The order of importance is:
     *
     * - package install path
     * - first found autoload namespace
     * - composer name
     *
     * @param string $composerName
     * @param string $packageType
     * @param string $packagePath
     * @param string $autoloadNamespace
     * @return string
     */
    protected function derivePackageKey($composerName, $packageType = null, $packagePath = null, $autoloadNamespace = null)
    {
        $packageKey = '';

        if ($packageType !== null && ComposerUtility::isFlowPackageType($packageType)) {
            $lastSegmentOfPackagePath = substr(trim($packagePath, '/'), strrpos(trim($packagePath, '/'), '/') + 1);
            if (strpos($lastSegmentOfPackagePath, '.') !== false) {
                $packageKey = $lastSegmentOfPackagePath;
            }
        }

        if ($autoloadNamespace !== null && ($packageKey === null || $this->isPackageKeyValid($packageKey) === false)) {
            $packageKey = str_replace('\\', '.', $autoloadNamespace);
        }

        if (($packageKey === null || $this->isPackageKeyValid($packageKey) === false)) {
            $packageKey = str_replace('/', '.', $composerName);
        }

        $packageKey = trim($packageKey, '.');
        $packageKey = preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);

        return $packageKey;
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
        if ($this->bootstrap === null) {
            return;
        }

        if ($this->dispatcher === null) {
            $this->dispatcher = $this->bootstrap->getEarlyInstance(Dispatcher::class);
        }

        $this->dispatcher->dispatch(PackageManager::class, 'packageStatesUpdated');
    }
}

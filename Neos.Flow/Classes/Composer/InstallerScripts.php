<?php
namespace Neos\Flow\Composer;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;

/**
 * Class for Composer install scripts
 */
class InstallerScripts
{
    /**
     * Make sure required paths and files are available outside of Package
     * Run on every Composer install or update - must be configured in root manifest
     *
     * @param Event $event
     * @return void
     */
    public static function postUpdateAndInstall(Event $event)
    {
        if (!defined('FLOW_PATH_ROOT')) {
            define('FLOW_PATH_ROOT', Files::getUnixStylePath(getcwd()) . '/');
        }

        if (!defined('FLOW_PATH_PACKAGES')) {
            define('FLOW_PATH_PACKAGES', Files::getUnixStylePath(getcwd()) . '/Packages/');
        }

        if (!defined('FLOW_PATH_CONFIGURATION')) {
            define('FLOW_PATH_CONFIGURATION', Files::getUnixStylePath(getcwd()) . '/Configuration/');
        }

        Files::createDirectoryRecursively('Configuration');
        Files::createDirectoryRecursively('Data');

        Files::copyDirectoryRecursively('Packages/Framework/Neos.Flow/Resources/Private/Installer/Distribution/Essentials', './', false, true);
        Files::copyDirectoryRecursively('Packages/Framework/Neos.Flow/Resources/Private/Installer/Distribution/Defaults', './', true, true);
        $packageManager = new PackageManager();
        $packageManager->rescanPackages();

        chmod('flow', 0755);
    }

    /**
     * Calls actions and install scripts provided by installed packages.
     *
     * @param PackageEvent $event
     * @return void
     * @throws Exception\UnexpectedOperationException
     */
    public static function postPackageUpdateAndInstall(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if (!$operation instanceof InstallOperation && !$operation instanceof UpdateOperation) {
            throw new Exception\UnexpectedOperationException('Handling of operation with type "' . $operation->getJobType() . '" not supported', 1348750840);
        }
        $package = ($operation->getJobType() === 'install') ? $operation->getPackage() : $operation->getTargetPackage();
        $packageExtraConfig = $package->getExtra();
        $installPath = $event->getComposer()->getInstallationManager()->getInstallPath($package);

        $evaluatedInstallerResources = false;
        if (isset($packageExtraConfig['neos']['installer-resource-folders'])) {
            foreach ($packageExtraConfig['neos']['installer-resource-folders'] as $installerResourceDirectory) {
                static::copyDistributionFiles($installPath . $installerResourceDirectory);
            }
            $evaluatedInstallerResources = true;
        }

        if ($operation->getJobType() === 'install') {
            if (isset($packageExtraConfig['typo3/flow']['post-install'])) {
                self::runPackageScripts($packageExtraConfig['typo3/flow']['post-install']);
            }
            if (isset($packageExtraConfig['neos/flow']['post-install'])) {
                self::runPackageScripts($packageExtraConfig['neos/flow']['post-install']);
            }
        }

        if ($operation->getJobType() === 'update') {
            if (isset($packageExtraConfig['typo3/flow']['post-update'])) {
                self::runPackageScripts($packageExtraConfig['typo3/flow']['post-update']);
            }
            if (isset($packageExtraConfig['neos/flow']['post-update'])) {
                self::runPackageScripts($packageExtraConfig['neos/flow']['post-update']);
            }
        }

        // TODO: Deprecated from Flow 3.1 remove three versions after.
        if (!$evaluatedInstallerResources && isset($packageExtraConfig['typo3/flow']['manage-resources']) && $packageExtraConfig['typo3/flow']['manage-resources'] === true) {
            static::copyDistributionFiles($installPath . 'Resources/Private/Installer/');
        }
    }

    /**
     * Copies any distribution files to their place if needed.
     *
     * @param string $installerResourcesDirectory Path to the installer directory that contains the Distribution/Essentials and/or Distribution/Defaults directories.
     * @return void
     */
    protected static function copyDistributionFiles($installerResourcesDirectory)
    {
        $essentialsPath = $installerResourcesDirectory . 'Distribution/Essentials';
        if (is_dir($essentialsPath)) {
            Files::copyDirectoryRecursively($essentialsPath, Files::getUnixStylePath(getcwd()) . '/', false, true);
        }

        $defaultsPath = $installerResourcesDirectory . 'Distribution/Defaults';
        if (is_dir($defaultsPath)) {
            Files::copyDirectoryRecursively($defaultsPath, Files::getUnixStylePath(getcwd()) . '/', true, true);
        }
    }

    /**
     * Calls a static method from it's string representation
     *
     * @param string $staticMethodReference
     * @return void
     * @throws Exception\InvalidConfigurationException
     */
    protected static function runPackageScripts($staticMethodReference)
    {
        $className = substr($staticMethodReference, 0, strpos($staticMethodReference, '::'));
        $methodName = substr($staticMethodReference, strpos($staticMethodReference, '::') + 2);

        if (!class_exists($className)) {
            throw new Exception\InvalidConfigurationException('Class "' . $className . '" is not autoloadable, can not call "' . $staticMethodReference . '"', 1348751076);
        }
        if (!is_callable($staticMethodReference)) {
            throw new Exception\InvalidConfigurationException('Method "' . $staticMethodReference . '" is not callable', 1348751082);
        }
        $className::$methodName();
    }
}

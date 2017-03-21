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

use Neos\Flow\Package\PackageInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Files;

/**
 * Utility to access composer information like composer manifests (composer.json) and the lock file.
 *
 * Meant to be used only inside the Flow package management code.
 */
class ComposerUtility
{
    /**
     * Runtime cache for composer.json data
     *
     * @var array
     */
    protected static $composerManifestCache;

    /**
     * Runtime cache for composer.lock data
     *
     * @var array
     */
    protected static $composerLockCache;

    /**
     * Returns contents of Composer manifest - or part there of.
     *
     * @param string $manifestPath
     * @param string $configurationPath Optional. Only return the part of the manifest indexed by configurationPath
     * @return array|mixed
     */
    public static function getComposerManifest($manifestPath, $configurationPath = null)
    {
        $composerManifest = static::readComposerManifest($manifestPath);
        if ($composerManifest === null) {
            return null;
        }

        if ($configurationPath !== null) {
            return ObjectAccess::getPropertyPath($composerManifest, $configurationPath);
        } else {
            return $composerManifest;
        }
    }

    /**
     * Read the content of the composer.lock
     *
     * @return array
     */
    public static function readComposerLock()
    {
        if (self::$composerLockCache !== null) {
            return self::$composerLockCache;
        }

        if (!file_exists(FLOW_PATH_ROOT . 'composer.lock')) {
            return [];
        }

        $json = file_get_contents(FLOW_PATH_ROOT . 'composer.lock');
        $composerLock = json_decode($json, true);
        $composerPackageVersions = isset($composerLock['packages']) ? $composerLock['packages'] : [];
        $composerPackageDevVersions = isset($composerLock['packages-dev']) ? $composerLock['packages-dev'] : [];
        self::$composerLockCache = array_merge($composerPackageVersions, $composerPackageDevVersions);

        return self::$composerLockCache;
    }

    /**
     * Read the content of composer.json in the given path
     *
     * @param string $manifestPath
     * @return array
     * @throws Exception\MissingPackageManifestException
     */
    protected static function readComposerManifest($manifestPath)
    {
        $manifestPathAndFilename = $manifestPath . 'composer.json';
        if (isset(self::$composerManifestCache[$manifestPathAndFilename])) {
            return self::$composerManifestCache[$manifestPathAndFilename];
        }

        if (!is_file($manifestPathAndFilename)) {
            throw new Exception\MissingPackageManifestException(sprintf('No composer manifest file found at "%s".', $manifestPathAndFilename), 1349868540);
        }
        $json = file_get_contents($manifestPathAndFilename);
        $composerManifest = json_decode($json, true);

        self::$composerManifestCache[$manifestPathAndFilename] = $composerManifest;
        return $composerManifest;
    }

    /**
     * Checks if the given (composer) package type is a type native to the neos project.
     *
     * @param string $packageType
     * @return boolean
     */
    public static function isFlowPackageType($packageType)
    {
        foreach (['typo3-flow-', 'neos-'] as $allowedPackageTypePrefix) {
            if (strpos($packageType, $allowedPackageTypePrefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines the composer package name ("vendor/foo-bar") from the Flow package key ("Vendor.Foo.Bar")
     *
     * @param string $packageKey
     * @return string
     */
    public static function getComposerPackageNameFromPackageKey($packageKey)
    {
        $nameParts = explode('.', $packageKey);
        $vendor = array_shift($nameParts);
        return strtolower($vendor . '/' . implode('-', $nameParts));
    }

    /**
     * Write a composer manifest for the package.
     *
     * @param string $manifestPath
     * @param string $packageKey
     * @param array $composerManifestData
     * @return array the manifest data written
     */
    public static function writeComposerManifest($manifestPath, $packageKey, array $composerManifestData = [])
    {
        $manifest = [
            'description' => ''
        ];

        if ($composerManifestData !== null) {
            $manifest = array_merge($manifest, $composerManifestData);
        }
        if (!isset($manifest['name']) || empty($manifest['name'])) {
            $manifest['name'] = static::getComposerPackageNameFromPackageKey($packageKey);
        }

        if (!isset($manifest['require']) || empty($manifest['require'])) {
            $manifest['require'] = array('neos/flow' => '*');
        }

        if (!isset($manifest['autoload'])) {
            $namespace = str_replace('.', '\\', $packageKey) . '\\';
            $manifest['autoload'] = array('psr-4' => array($namespace => PackageInterface::DIRECTORY_CLASSES));
        }

        $manifest['extra']['neos']['package-key'] = $packageKey;

        if (defined('JSON_PRETTY_PRINT')) {
            file_put_contents(Files::concatenatePaths(array($manifestPath, 'composer.json')), json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            file_put_contents(Files::concatenatePaths(array($manifestPath, 'composer.json')), json_encode($manifest));
        }

        return $manifest;
    }

    /**
     * Flushes the internal caches for manifest files and composer lock.
     *
     * @return void
     */
    public static function flushCaches()
    {
        static::$composerLockCache = [];
        static::$composerManifestCache = [];
    }
}

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

use Neos\Flow\Composer\ComposerUtility;
use Neos\Utility\Files;
use Neos\Flow\Utility\PhpAnalyzer;

/**
 * Class for building Packages
 */
class PackageFactory
{
    /**
     * Returns a package instance.
     *
     * @param string $packagesBasePath the base install path of packages,
     * @param string $packagePath path to package, relative to base path
     * @param string $packageKey key / name of the package
     * @param string $composerName
     * @param array $autoloadConfiguration Autoload configuration as defined in composer.json
     * @param array $packageClassInformation
     * @return PackageInterface
     * @throws Exception\CorruptPackageException
     */
    public function create($packagesBasePath, $packagePath, $packageKey, $composerName, array $autoloadConfiguration = [], array $packageClassInformation = null)
    {
        $absolutePackagePath = Files::concatenatePaths([$packagesBasePath, $packagePath]) . '/';

        if ($packageClassInformation === null) {
            $packageClassInformation = $this->detectFlowPackageFilePath($packageKey, $absolutePackagePath);
        }

        $packageClassName = Package::class;
        if (!empty($packageClassInformation)) {
            $packageClassName = $packageClassInformation['className'];
            $packageClassPath = Files::concatenatePaths(array($absolutePackagePath, $packageClassInformation['pathAndFilename']));
            require_once($packageClassPath);
        }

        $package = new $packageClassName($packageKey, $composerName, $absolutePackagePath, $autoloadConfiguration);
        if (!$package instanceof PackageInterface) {
            throw new Exception\CorruptPackageException(sprintf('The package class of package "%s" does not implement \Neos\Flow\Package\PackageInterface. Check the file "%s".', $packageKey, $packageClassInformation['pathAndFilename']), 1427193370);
        }

        return $package;
    }

    /**
     * Detects if the package contains a package file and returns the path and classname.
     *
     * @param string $packageKey The package key
     * @param string $absolutePackagePath Absolute path to the package
     * @return array The path to the package file and classname for this package or an empty array if none was found.
     * @throws Exception\CorruptPackageException
     * @throws Exception\InvalidPackagePathException
     */
    public function detectFlowPackageFilePath($packageKey, $absolutePackagePath)
    {
        if (!is_dir($absolutePackagePath)) {
            throw new Exception\InvalidPackagePathException(sprintf('The given package path "%s" is not a readable directory.', $absolutePackagePath), 1445904440);
        }

        $composerManifest = ComposerUtility::getComposerManifest($absolutePackagePath);
        if (!ComposerUtility::isFlowPackageType(isset($composerManifest['type']) ? $composerManifest['type'] : '')) {
            return [];
        }

        $possiblePackageClassPaths = [
            Files::concatenatePaths(['Classes', 'Package.php']),
            Files::concatenatePaths(['Classes', str_replace('.', '/', $packageKey), 'Package.php'])
        ];

        $foundPackageClassPaths = array_filter($possiblePackageClassPaths, function ($packageClassPathAndFilename) use ($absolutePackagePath) {
            $absolutePackageClassPath = Files::concatenatePaths([$absolutePackagePath, $packageClassPathAndFilename]);
            return is_file($absolutePackageClassPath);
        });

        if ($foundPackageClassPaths === []) {
            return [];
        }

        if (count($foundPackageClassPaths) > 1) {
            throw new Exception\CorruptPackageException(sprintf('The package "%s" contains multiple possible "Package.php" files. Please make sure that only one "Package.php" exists in the autoload root(s) of your Flow package.', $packageKey), 1457454840);
        }

        $packageClassPathAndFilename = reset($foundPackageClassPaths);
        $absolutePackageClassPath = Files::concatenatePaths([$absolutePackagePath, $packageClassPathAndFilename]);

        $packageClassContents = file_get_contents($absolutePackageClassPath);
        $packageClassName = (new PhpAnalyzer($packageClassContents))->extractFullyQualifiedClassName();
        if ($packageClassName === null) {
            throw new Exception\CorruptPackageException(sprintf('The package "%s" does not contain a valid package class. Check if the file "%s" really contains a class.', $packageKey, $packageClassPathAndFilename), 1327587091);
        }

        return ['className' => $packageClassName, 'pathAndFilename' => $packageClassPathAndFilename];
    }
}

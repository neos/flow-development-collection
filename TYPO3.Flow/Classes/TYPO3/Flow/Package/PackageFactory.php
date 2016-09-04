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

use TYPO3\Flow\Package\Exception\InvalidPackageManifestException;
use TYPO3\Flow\Package\Exception\MissingPackageManifestException;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\PhpAnalyzer;

/**
 * Class for building Packages
 */
class PackageFactory
{
    /**
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @param PackageManagerInterface $packageManager
     */
    public function __construct(PackageManagerInterface $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Returns a package instance.
     *
     * @param string $packagesBasePath the base install path of packages,
     * @param string $packagePath path to package, relative to base path
     * @param string $packageKey key / name of the package
     * @param string $classesPath path to the classes directory, relative to the package path
     * @param string $manifestPath path to the package's Composer manifest, relative to package path, defaults to same path
     * @return PackageInterface
     * @throws Exception\CorruptPackageException
     */
    public function create($packagesBasePath, $packagePath, $packageKey, $classesPath = null, $manifestPath = null)
    {
        $absolutePackagePath = Files::concatenatePaths([$packagesBasePath, $packagePath]) . '/';
        $absoluteManifestPath = $manifestPath === null ? $absolutePackagePath : Files::concatenatePaths([$absolutePackagePath, $manifestPath]) . '/';
        $autoLoadDirectives = [];
        try {
            $autoLoadDirectives = (array)PackageManager::getComposerManifest($absoluteManifestPath, 'autoload');
        } catch (MissingPackageManifestException $exception) {
        }
        if (isset($autoLoadDirectives[Package::AUTOLOADER_TYPE_PSR4])) {
            $packageClassPathAndFilename = Files::concatenatePaths([$absolutePackagePath, 'Classes', 'Package.php']);
        } else {
            $packageClassPathAndFilename = Files::concatenatePaths([$absolutePackagePath, 'Classes', str_replace('.', '/', $packageKey), 'Package.php']);
        }
        $package = null;
        if (file_exists($packageClassPathAndFilename)) {
            require_once($packageClassPathAndFilename);
            $packageClassContents = file_get_contents($packageClassPathAndFilename);
            $packageClassName = (new PhpAnalyzer($packageClassContents))->extractFullyQualifiedClassName();
            if ($packageClassName === null) {
                throw new Exception\CorruptPackageException(sprintf('The package "%s" does not contain a valid package class. Check if the file "%s" really contains a class.', $packageKey, $packageClassPathAndFilename), 1327587091);
            }
            $package = new $packageClassName($this->packageManager, $packageKey, $absolutePackagePath, $classesPath, $manifestPath);
            if (!$package instanceof PackageInterface) {
                throw new Exception\CorruptPackageException(sprintf('The package class of package "%s" does not implement %s. Check the file "%s".', $packageKey, PackageInterface::class, $packageClassPathAndFilename), 1427193370);
            }
            return $package;
        }
        return new Package($this->packageManager, $packageKey, $absolutePackagePath, $classesPath, $manifestPath);
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
     * @param object $manifest
     * @param string $packagePath
     * @param string $packagesBasePath
     * @return string
     * @throws InvalidPackageManifestException
     */
    public static function getPackageKeyFromManifest($manifest, $packagePath, $packagesBasePath)
    {
        if (!is_object($manifest)) {
            throw new  InvalidPackageManifestException('Invalid composer manifest.', 1348146450);
        }
        if (isset($manifest->type) && substr($manifest->type, 0, 11) === 'typo3-flow-') {
            $relativePackagePath = substr($packagePath, strlen($packagesBasePath));
            $packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
            /**
             * @todo check that manifest name and directory follows convention
             */
        } else {
            $packageKey = str_replace('/', '.', $manifest->name);
            if (isset($manifest->autoload) && isset($manifest->autoload->{"psr-0"})) {
                $namespaces = array_keys(get_object_vars($manifest->autoload->{"psr-0"}));
                foreach ($namespaces as $namespace) {
                    $namespaceLead = substr($namespace, 0, strlen($manifest->name));
                    $dottedNamespaceLead = str_replace('\\', '.', $namespaceLead);
                    if (strtolower($dottedNamespaceLead) === $packageKey) {
                        $packageKey = $dottedNamespaceLead;
                    }
                }
            }
        }
        $packageKey = preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);
        return $packageKey;
    }
}

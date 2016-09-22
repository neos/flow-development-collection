<?php
namespace TYPO3\Flow\Resource\Storage;

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
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Resource\Resource as PersistentResource;
use TYPO3\Flow\Resource\Storage\Object as StorageObject;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * A resource storage which stores and retrieves resources from active Flow packages.
 */
class PackageStorage extends FileSystemStorage
{
    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * Initializes this resource storage
     *
     * @return void
     */
    public function initializeObject()
    {
        // override the parent method because we don't need that here
    }

    /**
     * Retrieve all Objects stored in this storage.
     *
     * @return array<StorageObject>
     */
    public function getObjects()
    {
        return $this->getObjectsByPathPattern('*');
    }

    /**
     * Return all Objects stored in this storage filtered by the given directory / filename pattern
     *
     * @param string $pattern A glob compatible directory / filename pattern
     * @return array<StorageObject>
     */
    public function getObjectsByPathPattern($pattern)
    {
        $objects = [];
        $directories = [];

        if (strpos($pattern, '/') !== false) {
            list($packageKeyPattern, $directoryPattern) = explode('/', $pattern, 2);
        } else {
            $packageKeyPattern = $pattern;
            $directoryPattern = '*';
        }
        // $packageKeyPattern can be used in a future implementation to filter by package key

        $packages = $this->packageManager->getActivePackages();
        foreach ($packages as $packageKey => $package) {
            /** @var PackageInterface $package */
            if ($directoryPattern === '*') {
                $directories[$packageKey][] = $package->getPackagePath();
            } else {
                $directories[$packageKey] = glob($package->getPackagePath() . $directoryPattern, GLOB_ONLYDIR);
            }
        }

        foreach ($directories as $packageKey => $packageDirectories) {
            foreach ($packageDirectories as $directoryPath) {
                foreach (Files::readDirectoryRecursively($directoryPath) as $resourcePathAndFilename) {
                    $pathInfo = UnicodeFunctions::pathinfo($resourcePathAndFilename);

                    $object = new Object();
                    $object->setFilename($pathInfo['basename']);
                    $object->setSha1(sha1_file($resourcePathAndFilename));
                    $object->setMd5(md5_file($resourcePathAndFilename));
                    $object->setFileSize(filesize($resourcePathAndFilename));
                    if (isset($pathInfo['dirname'])) {
                        list(, $path) = explode('/', str_replace($packages[$packageKey]->getResourcesPath(), '', $pathInfo['dirname']), 2);
                        $object->setRelativePublicationPath($packageKey . '/' . $path . '/');
                    }
                    $object->setStream(function () use ($resourcePathAndFilename) {
                        return fopen($resourcePathAndFilename, 'r');
                    });
                    $objects[] = $object;
                }
            }
        }

        return $objects;
    }

    /**
     * Because we cannot store persistent resources in a PackageStorage, this method always returns FALSE.
     *
     * @param PersistentResource $resource The resource stored in this storage
     * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
     */
    public function getStreamByResource(PersistentResource $resource)
    {
        return false;
    }

    /**
     * Returns the absolute paths of public resources directories of all active packages.
     * This method is used directly by the FileSystemSymlinkTarget.
     *
     * @return array<string>
     */
    public function getPublicResourcePaths()
    {
        $paths = [];
        $packages = $this->packageManager->getActivePackages();
        foreach ($packages as $packageKey => $package) {
            /** @var PackageInterface $package */
            $publicResourcesPath = Files::concatenatePaths([$package->getResourcesPath(), 'Public']);
            if (is_dir($publicResourcesPath)) {
                $paths[$packageKey] = $publicResourcesPath;
            }
        }
        return $paths;
    }
}

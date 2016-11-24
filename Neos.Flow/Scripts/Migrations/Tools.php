<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Files;

/**
 * A set of helper methods for the code migration tool.
 */
class Tools
{
    /**
     * Will return an array with all available packages.
     *
     * The data for each entry will be an array with the key, full path to
     * the package (index 'path') and a category (the packages subfolder,
     * index 'category'). The array is indexed by package key.
     *
     * @param string $packagesPath
     * @return array
     */
    public static function getPackagesData($packagesPath)
    {
        $packagesData = array();
        $packagesDirectoryIterator = new \DirectoryIterator($packagesPath);
        foreach ($packagesDirectoryIterator as $categoryFileInfo) {
            $category = $categoryFileInfo->getFilename();
            if (!$categoryFileInfo->isDir() || $category[0] === '.' || $category === 'Libraries') {
                continue;
            }

            $categoryDirectoryIterator = new \DirectoryIterator($categoryFileInfo->getPathname());
            foreach ($categoryDirectoryIterator as $packageFileInfo) {
                $packageKey = $packageFileInfo->getFilename();
                if (!$packageFileInfo->isDir() || $packageKey[0] === '.') {
                    continue;
                }

                $meta = self::readPackageMetaData(Files::concatenatePaths(array($packageFileInfo->getPathname(), 'Meta/Package.xml')));
                $composerManifest = self::readComposerManifest(Files::concatenatePaths(array($packageFileInfo->getPathname(), 'composer.json')));

                $packagesData[$packageKey] = array(
                    'packageKey' => $packageKey,
                    'category' => $category,
                    'path' => $packageFileInfo->getPathname(),
                    'meta' => $meta,
                    'composerManifest' => $composerManifest
                );
            }
        }
        return $packagesData;
    }

    /**
     * Read the package manifest from the composer.json file at $pathAndFileName
     *
     * @param string $pathAndFileName
     * @return array
     */
    public static function readComposerManifest($pathAndFileName)
    {
        if (file_exists($pathAndFileName)) {
            $json = file_get_contents($pathAndFileName);
            return json_decode($json, true);
        } else {
            return null;
        }
    }

    /**
     * Write the manifest to the given file.
     *
     * @param array $manifest
     * @param string $pathAndFilename
     * @return void
     */
    public static function writeComposerManifest(array $manifest, $pathAndFilename)
    {
        file_put_contents($pathAndFilename, json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Read the package metadata from the Package.xml file at $pathAndFileName
     *
     * @param string $pathAndFileName
     * @return array|NULL
     */
    protected static function readPackageMetaData($pathAndFileName)
    {
        if (file_exists($pathAndFileName)) {
            $xml = simplexml_load_file($pathAndFileName);
            $meta = array();
            if ($xml === false) {
                $meta['description'] = '[Package.xml could not be read.]';
            } else {
                $meta['version'] = (string)$xml->version;
                $meta['title'] = (string)$xml->title;
                $meta['description'] = (string)$xml->description;
            }

            return $meta;
        } else {
            return null;
        }
    }

    /**
     * Does a search and replace operation on the given file.
     *
     * A simple str_replace is used, unless $regularExpression is set
     * to TRUE. In that case preg_replace is used. The given patterns
     * are used as given, no quoting is applied!
     *
     * In case $regularExpression is TRUE, a closure can be given for
     * the $replace variable. It should return a string and is given an
     * array of matches as parameter.
     *
     * @param string $search
     * @param string|\Closure $replace
     * @param string $pathAndFilename
     * @param boolean $regularExpression
     * @return boolean|NULL FALSE on errors, NULL on skip, TRUE on success
     */
    public static function searchAndReplace($search, $replace, $pathAndFilename, $regularExpression = false)
    {
        $pathInfo = pathinfo($pathAndFilename);
        if (!isset($pathInfo['filename']) || $pathAndFilename === __FILE__) {
            return false;
        }

        $file = file_get_contents($pathAndFilename);
        $fileBackup = $file;
        if ($regularExpression === true) {
            if ($replace instanceof \Closure) {
                $file = preg_replace_callback($search, $replace, $file);
            } else {
                $file = preg_replace($search, $replace, $file);
            }
        } else {
            $file = str_replace($search, $replace, $file);
        }
        if ($file !== $fileBackup) {
            file_put_contents($pathAndFilename, $file);
            return true;
        }
        return null;
    }
}

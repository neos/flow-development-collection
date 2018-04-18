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

/**
 * Interface for a basic Package class
 *
 * @api
 */
interface PackageInterface
{
    const PATTERN_MATCH_PACKAGEKEY = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+$/i';
    const DEFAULT_COMPOSER_TYPE = 'neos-package';

    /**
     * Returns the array of filenames of the class files
     *
     * @return array An array of class names (key) and their filename, including the relative path to the package's directory
     * @api
     */
    public function getClassFiles();

    /**
     * Returns the composer name of this package.
     *
     * @return string
     * @api
     */
    public function getComposerName();

    /**
     * Returns an array of all namespaces declared for this package.
     *
     * @return array
     * @api
     */
    public function getNamespaces();

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     * @api
     */
    public function getPackagePath();


    /**
     * Returns the currently installed version of this package.
     *
     * @return string
     * @api
     */
    public function getInstalledVersion();

    /**
     * Returns the composer manifest of this package or
     * just contents of a specific key of the full configuration.
     *
     * @param string $key
     * @return mixed
     * @api
     */
    public function getComposerManifest($key = null);
}

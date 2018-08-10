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
 * An interface for Flow packages that might have configuration or resources
 */
interface FlowPackageInterface extends PackageInterface, PackageKeyAwareInterface
{
    const DIRECTORY_CLASSES = 'Classes/';
    const DIRECTORY_CONFIGURATION = 'Configuration/';
    const DIRECTORY_TESTS_FUNCTIONAL = 'Tests/Functional/';
    const DIRECTORY_TESTS_UNIT = 'Tests/Unit/';
    const DIRECTORY_RESOURCES = 'Resources/';

    /**
     * Returns the full path to this package's Resources directory
     *
     * @return string Path to this package's Resources directory
     * @api
     */
    public function getResourcesPath();

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     * @api
     */
    public function getConfigurationPath();

    /**
     * Returns a generator of filenames of class files provided by functional tests contained in this package
     *
     * @return \Generator
     * @internal
     */
    public function getFunctionalTestsClassFiles();

    /**
     * Returns the full path to this package's functional tests directory
     *
     * @return string Path to this package's functional tests directory
     * @internal
     */
    public function getFunctionalTestsPath();
}

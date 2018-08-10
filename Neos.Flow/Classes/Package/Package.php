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

use Neos\Utility\Files;
use Neos\Flow\Core\Bootstrap;

/**
 * A Flow Package
 *
 * @api
 */
class Package extends GenericPackage implements FlowPackageInterface, BootablePackageInterface
{
    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
    }

    /**
     * Returns a generator of filenames of class files provided by functional tests contained in this package
     *
     * @return \Generator A generator of class names (key) and their filename, including the relative path to the package's directory
     * @internal
     */
    public function getFunctionalTestsClassFiles()
    {
        $namespaces = $this->getNamespaces();
        if (is_dir($this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL)) {
            // TODO REFACTOR replace with usage of "autoload-dev"
            $namespacePrefix = str_replace('/', '\\', Files::concatenatePaths([
                reset($namespaces),
                '\\Tests\\Functional\\'
            ]));
            foreach ($this->getClassesInNormalizedAutoloadPath($this->packagePath . FlowPackageInterface::DIRECTORY_TESTS_FUNCTIONAL, $namespacePrefix) as $className => $classPath) {
                yield $className => $classPath;
            }
        }
    }

    /**
     * Returns the full path to this package's functional tests directory
     *
     * @return string Path to this package's functional tests directory
     * @internal
     * TODO: Should be replaced by using autoload-dev
     */
    public function getFunctionalTestsPath()
    {
        return $this->packagePath . FlowPackageInterface::DIRECTORY_TESTS_FUNCTIONAL;
    }

    /**
     * Returns the full path to this package's Resources directory
     *
     * @return string Path to this package's Resources directory
     * @api
     */
    public function getResourcesPath()
    {
        return $this->packagePath . FlowPackageInterface::DIRECTORY_RESOURCES;
    }

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     * @api
     */
    public function getConfigurationPath()
    {
        return $this->packagePath . FlowPackageInterface::DIRECTORY_CONFIGURATION;
    }
}

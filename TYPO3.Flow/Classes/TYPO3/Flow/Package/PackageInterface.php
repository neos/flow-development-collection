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

/**
 * Interface for a Flow Package class
 *
 * @api
 */
interface PackageInterface
{
    const PATTERN_MATCH_PACKAGEKEY = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+$/i';

    const DIRECTORY_CLASSES = 'Classes/';
    const DIRECTORY_CONFIGURATION = 'Configuration/';
    const DIRECTORY_DOCUMENTATION = 'Documentation/';
    const DIRECTORY_METADATA = 'Meta/';
    const DIRECTORY_TESTS_FUNCTIONAL = 'Tests/Functional/';
    const DIRECTORY_TESTS_UNIT = 'Tests/Unit/';
    const DIRECTORY_RESOURCES = 'Resources/';

    const DEFAULT_COMPOSER_TYPE = 'neos-package';

    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap);

    /**
     * Returns the package meta object of this package.
     *
     * @return \TYPO3\Flow\Package\MetaData
     */
    public function getPackageMetaData();

    /**
     * Returns the array of filenames of the class files
     *
     * @return array An array of class names (key) and their filename, including the relative path to the package's directory
     * @api
     */
    public function getClassFiles();

    /**
     * Returns the package key of this package.
     *
     * @return string
     * @api
     */
    public function getPackageKey();

    /**
     * Returns the PHP namespace of classes in this package.
     *
     * @return string
     * @api
     * @deprecated Use getNamespaces() - To be removed in Flow 4.0
     */
    public function getNamespace();

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return boolean
     * @api
     */
    public function isProtected();

    /**
     * Tells if files in the Classes directory should be registered and object management enabled for this package.
     *
     * @return boolean
     */
    public function isObjectManagementEnabled();

    /**
     * Sets the protection flag of the package
     *
     * @param boolean $protected TRUE if the package should be protected, otherwise FALSE
     * @return void
     * @api
     */
    public function setProtected($protected);

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     * @api
     */
    public function getPackagePath();

    /**
     * Returns the full path to this package's Classes directory
     *
     * @return string Path to this package's Classes directory
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getClassesPath();

    /**
     * Returns the full path to the package's classes namespace entry path,
     * e.g. "My.Package/ClassesPath/My/Package/"
     *
     * @return string Path to this package's Classes directory
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getClassesNamespaceEntryPath();

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
     * Returns the full path to this package's Package.xml file
     *
     * @return string Path to this package's Package.xml file
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getMetaPath();

    /**
     * Returns the full path to the package's documentation directory
     *
     * @return string Full path to the package's documentation directory
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getDocumentationPath();

    /**
     * Returns the available documentations for this package
     *
     * @return array Array of \TYPO3\Flow\Package\Documentation
     * @api
     * @deprecated To be removed in Flow 4.0
     */
    public function getPackageDocumentations();
}

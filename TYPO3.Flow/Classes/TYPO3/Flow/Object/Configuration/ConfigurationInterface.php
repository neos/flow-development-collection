<?php
namespace TYPO3\Flow\Object\Configuration;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
interface ConfigurationInterface
{

    /**
     * @return string
     */
    public function getObjectName();

    /**
     * @return string
     */
    public function getClassName();

    /**
     * @return string
     */
    public function getPackageKey();

    /**
     * @return string
     */
    public function getFactoryObjectName();

    /**
     * @return string
     */
    public function getFactoryMethodName();

    /**
     * @return boolean
     */
    public function isCreatedByFactory();

    /**
     * @return string The scope, one of the SCOPE constants
     */
    public function getScope();

    /**
     * @return integer Value of one of the AUTOWIRING_MODE_* constants
     */
    public function getAutowiring();

    /**
     * @return string
     */
    public function getLifecycleInitializationMethodName();

    /**
     * @return string
     */
    public function getLifecycleShutdownMethodName();

    /**
     * @return array<TYPO3\Flow\Object\Configuration\ConfigurationProperty>
     */
    public function getProperties();

    /**
     * @return array A sorted array of \TYPO3\Flow\Object\Configuration\ConfigurationArgument objects with the argument position as index
     */
    public function getArguments();

    /**
     * @return string
     */
    public function getConfigurationSourceHint();
}

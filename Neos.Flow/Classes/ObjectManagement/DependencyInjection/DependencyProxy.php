<?php
namespace Neos\Flow\ObjectManagement\DependencyInjection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A Proxy Class Builder which integrates Dependency Injection.
 *
 * @Flow\Proxy(false)
 * @api
 */
interface DependencyProxy
{
    /**
     * Activate the dependency and set it in the object.
     *
     * @return object The real dependency object
     * @api
     */
    public function _activateDependency();

    /**
     * Returns the class name of the proxied dependency
     *
     * @return string Fully qualified class name of the proxied object
     * @api
     */
    public function _getClassName();

    /**
     * Adds another variable by reference where the actual dependency object should
     * be injected into once this proxy is activated.
     *
     * @param mixed &$propertyVariable The variable to replace
     * @return void
     */
    public function _addPropertyVariable(&$propertyVariable);
}

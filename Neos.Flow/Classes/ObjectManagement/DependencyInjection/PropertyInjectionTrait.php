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

/**
 * Boilerplate code for dependency injection
 */
trait PropertyInjectionTrait
{
    /**
     * Does a property injection lazily with fallbacks.
     * Used in proxy classes.
     *
     * @param string $propertyObjectName
     * @param string $propertyClassName
     * @param string $propertyName
     * @param string $setterArgumentHash
     * @param callable $lazyInjectionResolver
     * @return void
     */
    private function Flow_Proxy_LazyPropertyInjection($propertyObjectName, $propertyClassName, $propertyName, $setterArgumentHash, callable $lazyInjectionResolver)
    {
        $injection_reference = &$this->$propertyName;
        $this->$propertyName = \Neos\Flow\Core\Bootstrap::$staticObjectManager->getInstance($propertyObjectName);
        if ($this->$propertyName === null) {
            $this->$propertyName = \Neos\Flow\Core\Bootstrap::$staticObjectManager->getLazyDependencyByHash($setterArgumentHash, $injection_reference);
            if ($this->$propertyName === null) {
                $this->$propertyName = \Neos\Flow\Core\Bootstrap::$staticObjectManager->createLazyDependency($setterArgumentHash, $injection_reference, $propertyClassName, $lazyInjectionResolver);
            }
        }
    }
}

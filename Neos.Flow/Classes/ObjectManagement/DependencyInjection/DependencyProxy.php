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
 * @deprecated since Flow 9.2, not used in the Framework anymore. Flow now uses PHP 8.4 lazy objects
 * @template T of object
 */
final class DependencyProxy
{
    /**
     * @var array
     */
    protected array $propertyVariables = [];

    /**
     * Constructs this proxy
     *
     * @param class-string<T> $className Implementation class name of the dependency to proxy
     * @param \Closure $builder The closure which eventually builds the dependency
     */
    public function __construct(
        protected string $className,
        protected \Closure $builder
    ) {
    }

    /**
     * Activate the dependency and set it in the object.
     *
     * @return T The real dependency object
     * @api
     */
    public function _activateDependency(): object
    {
        $realDependency = $this->builder->__invoke();
        foreach ($this->propertyVariables as &$propertyVariable) {
            $propertyVariable = $realDependency;
        }
        return $realDependency;
    }

    /**
     * Returns the class name of the proxied dependency
     *
     * @return class-string Fully qualified class name of the proxied object
     * @api
     */
    public function _getClassName(): string
    {
        return $this->className;
    }

    /**
     * Adds another variable by reference where the actual dependency object should
     * be injected into once this proxy is activated.
     *
     * @param mixed &$propertyVariable The variable to replace
     * @return void
     */
    public function _addPropertyVariable(&$propertyVariable): void
    {
        $this->propertyVariables[] = &$propertyVariable;
    }

    /**
     * Proxy magic call method which triggers the injection of the real dependency
     * and returns the result of a call to the original method in the dependency
     *
     * @param string $methodName Name of the method to be called
     * @param array $arguments An array of arguments to be passed to the method
     * @return mixed
     */
    public function __call(string $methodName, array $arguments): mixed
    {
        return $this->_activateDependency()->$methodName(...$arguments);
    }
}

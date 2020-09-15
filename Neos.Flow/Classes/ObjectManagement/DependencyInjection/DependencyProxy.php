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
class DependencyProxy
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var \Closure
     */
    protected $builder;

    /**
     * @var array
     */
    protected $propertyVariables = [];

    /**
     * Constructs this proxy
     *
     * @param string $className Implementation class name of the dependency to proxy
     * @param \Closure $builder The closure which eventually builds the dependency
     */
    public function __construct($className, \Closure $builder)
    {
        $this->className = $className;
        $this->builder = $builder;
    }

    /**
     * Activate the dependency and set it in the object.
     *
     * @return object The real dependency object
     * @api
     */
    public function _activateDependency()
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
     * @return string Fully qualified class name of the proxied object
     * @api
     */
    public function _getClassName()
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
    public function _addPropertyVariable(&$propertyVariable)
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
    public function __call($methodName, array $arguments)
    {
        return call_user_func_array([$this->_activateDependency(), $methodName], $arguments);
    }
}

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

trait DependencyProxyTrait
{
    /**
     * @var string
     */
    protected $_dependencyClassName;

    /**
     * @var \Closure
     */
    protected $_dependencyBuilder;

    /**
     * @var array
     */
    protected $_dependencyPropertyVariables = [];

    /**
     * Override any possibly existing constructor
     */
    public function __construct()
    {
    }

    /**
     * Constructs this proxy
     *
     * @param string $className Implementation class name of the dependency to proxy
     * @param \Closure $builder The closure which eventually builds the dependency
     * @return static
     */
    public static function _createDependencyProxy(string $className, \Closure $builder)
    {
        $instance = new static();
        $instance->_dependencyClassName = $className;
        $instance->_dependencyBuilder = $builder;
        return $instance;
    }

    /**
     * Activate the dependency and set it in the object.
     *
     * @return object The real dependency object
     * @api
     */
    public function _activateDependency()
    {
        $realDependency = $this->_dependencyBuilder->__invoke();
        foreach ($this->_dependencyPropertyVariables as &$propertyVariable) {
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
    public function _getClassName(): string
    {
        return $this->_dependencyClassName;
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
        $this->_dependencyPropertyVariables[] = &$propertyVariable;
    }
}

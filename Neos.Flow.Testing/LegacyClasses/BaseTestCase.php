<?php

declare(strict_types=1);

namespace Neos\Flow\Tests;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The mother of all test cases.
 *
 * Don't sub class this test case but rather choose a more specialized base test case,
 * such as UnitTestCase or FunctionalTestCase
 *
 * @deprecated its utilities are deprecated. Will be removed with Neos.Flow 10.
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @var array
     */
    protected $backupGlobalsExcludeList = ['GLOBALS', 'bootstrap', '__PHPUNIT_BOOTSTRAP'];

    /**
     * @var boolean
     */
    protected $backupStaticAttributes = false;

    /**
     * Returns a mock object which allows for calling protected methods and access
     * of protected properties.
     *
     * @template T of object
     * @param class-string<T> $originalClassName Full qualified name of the original class
     * @param array $methods
     * @param array $arguments
     * @param string $mockClassName
     * @param boolean $callOriginalConstructor
     * @param boolean $callOriginalClone
     * @param boolean $cloneArguments
     * @return T&MockObject&AccessibleProxyInterface
     * @deprecated please don't use this {@see AccessibleProxyInterface}
     */
    protected function getAccessibleMock(string $originalClassName, array $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $cloneArguments = false)
    {
        $mockBuilder = $this->getMockBuilder($this->buildAccessibleProxy($originalClassName));
        // PHPUnit 10+ rejects onlyMethods() entries that don't exist on the class. Split
        // off non-existing methods (e.g. AOP-emitted signal methods, magic methods) so they
        // can be added via addMethods() instead of failing the mock build.
        $existingMethods = [];
        $addedMethods = [];
        foreach ($methods as $method) {
            if (method_exists($originalClassName, $method)) {
                $existingMethods[] = $method;
            } else {
                $addedMethods[] = $method;
            }
        }
        $mockBuilder->onlyMethods($existingMethods)->setConstructorArgs($arguments)->setMockClassName($mockClassName);
        if ($addedMethods !== []) {
            $mockBuilder->addMethods($addedMethods);
        }
        if ($callOriginalConstructor === false) {
            $mockBuilder->disableOriginalConstructor();
        }
        if ($callOriginalClone === false) {
            $mockBuilder->disableOriginalClone();
        }
        if ($cloneArguments === true) {
            $mockBuilder->enableArgumentCloning();
        }

        $mockObject = $mockBuilder->getMock();

        $this->registerMockObject($mockObject);

        return $mockObject;
    }

    /**
     * Returns a mock object which allows for calling protected methods and access
     * of protected properties.
     *
     * @template T of object
     * @param class-string<T> $originalClassName Full qualified name of the original class
     * @param array $arguments
     * @param string $mockClassName
     * @param boolean $callOriginalConstructor
     * @param boolean $callOriginalClone
     * @param boolean $callAutoload
     * @param array $mockedMethods
     * @param boolean $cloneArguments
     * @return T&MockObject&AccessibleProxyInterface
     * @deprecated please don't use this {@see AccessibleProxyInterface}
     */
    protected function getAccessibleMockForAbstractClass($originalClassName, array $arguments = [], $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $mockedMethods = [], $cloneArguments = false)
    {
        return $this->getMockForAbstractClass($this->buildAccessibleProxy($originalClassName), $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload, $mockedMethods, $cloneArguments);
    }

    /**
     * Creates a proxy class of the specified class which allows
     * for calling even protected methods and access of protected properties.
     *
     * @param string $className Full qualified name of the original class
     * @return string Full qualified name of the built class
     * @deprecated please don't use this {@see AccessibleProxyInterface}
     */
    protected function buildAccessibleProxy($className)
    {
        $accessibleClassName = 'AccessibleTestProxy' . md5(uniqid((string)mt_rand(), true));
        $class = new \ReflectionClass($className);
        $abstractModifier = $class->isAbstract() ? 'abstract ' : '';
        eval('#[\AllowDynamicProperties]
			' . $abstractModifier . 'class ' . $accessibleClassName . ' extends ' . $className . ' {
				public function _call($methodName) {
					return call_user_func_array(array($this, $methodName), array_slice(func_get_args(), 1));
				}
				public function _callRef($methodName, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL, &$arg5= NULL, &$arg6 = NULL, &$arg7 = NULL, &$arg8 = NULL, &$arg9 = NULL) {
					switch (func_num_args()) {
						case 0 : return $this->$methodName();
						case 1 : return $this->$methodName($arg1);
						case 2 : return $this->$methodName($arg1, $arg2);
						case 3 : return $this->$methodName($arg1, $arg2, $arg3);
						case 4 : return $this->$methodName($arg1, $arg2, $arg3, $arg4);
						case 5 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5);
						case 6 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
						case 7 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7);
						case 8 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8);
						case 9 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8, $arg9);
					}
				}
				public function _set($propertyName, $value) {
					$this->$propertyName = $value;
				}
				public function _setRef($propertyName, &$value) {
					$this->$propertyName = $value;
				}
				public function _get($propertyName) {
					return $this->$propertyName;
				}
			}
		');

        return $accessibleClassName;
    }

    /**
     * Injects $dependency into property $name of $target
     *
     * This is a convenience method for setting a protected or private property in
     * a test subject for the purpose of injecting a dependency.
     *
     * @param object $target The instance which needs the dependency
     * @param string $name Name of the property to be injected
     * @param mixed $dependency The dependency to inject – usually an object but can also be any other type
     * @return void
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @deprecated please specify properties via constructor, call the injector manually or use - if you must - ObjectAccess::setProperty instead.
     */
    protected function inject(object $target, string $name, mixed $dependency)
    {
        // we don't use ObjectAccess::setProperty as it doesn't support `inject*`
        $objectReflection = new \ReflectionObject($target);
        $methodNamePart = strtoupper($name[0]) . substr($name, 1);
        if ($objectReflection->hasMethod('set' . $methodNamePart)) {
            $methodName = 'set' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
            $methodName = 'inject' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasProperty($name)) {
            $property = $objectReflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($target, $dependency);
        } else {
            throw new \RuntimeException('Could not inject ' . $name . ' into object of type ' . get_class($target));
        }
    }
}

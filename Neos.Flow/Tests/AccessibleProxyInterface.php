<?php

namespace Neos\Flow\Tests;

use Neos\Utility\ObjectAccess;

/**
 * This interface defines the methods provided by {@see BaseTestCase::getAccessibleMock()}
 * Do not implement this interface in own classes.
 *
 * It allows for calling even protected methods and access of protected properties.
 *
 * Note that this interface is not actually implemented by the accessible proxy, but only provides IDE support.
 *
 * @deprecated you should not use this for testing. As it will couple the tests to highly to the internal implementation
 * and makes refactorings without rewriting major tests impossible.
 */
interface AccessibleProxyInterface
{
    /** @deprecated */
    public function _call(string $methodName, mixed ...$arguments): mixed;
    /** @deprecated */
    public function _callRef(string $methodName, mixed ...$argumentsPassedByReference): mixed;
    /** @deprecated please specify properties via constructor call the injector manually or use - if you must - {@see ObjectAccess::setProperty()} instead. */
    public function _set(string $propertyName, mixed $value): void;
    /** @deprecated */
    public function _setRef(string $propertyName, mixed &$value): void;
    /** @deprecated please use - if you must - {@see ObjectAccess::setProperty()} instead. */
    public function _get(string $propertyName): mixed;
}

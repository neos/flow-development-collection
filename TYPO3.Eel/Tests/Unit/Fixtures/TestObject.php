<?php
namespace TYPO3\Eel\Tests\Unit\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * Test fixture object
 */
class TestObject implements ProtectedContextAwareInterface
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var boolean
     */
    protected $booleanProperty;

    /**
     * @var string
     */
    protected $dynamicMethodName;

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param $property
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @param boolean $booleanProperty
     */
    public function setBooleanProperty($booleanProperty)
    {
        $this->booleanProperty = $booleanProperty;
    }

    /**
     * @return boolean
     */
    public function isBooleanProperty()
    {
        return $this->booleanProperty;
    }

    /**
     * @param string $name
     * @return string
     */
    public function callMe($name)
    {
        return 'Hello, ' . $name . '!';
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return $methodName === $this->dynamicMethodName;
    }

    /**
     * @param string $dynamicMethodName
     */
    public function setDynamicMethodName($dynamicMethodName)
    {
        $this->dynamicMethodName = $dynamicMethodName;
    }
}

<?php
namespace Neos\Eel\Tests\Unit\Fixtures;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;

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

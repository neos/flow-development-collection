<?php
namespace Neos\Flow\Tests\Reflection\Fixture;

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
 * Fixture class with getters and setters
 *
 */
class DummyClassWithGettersAndSetters
{
    protected $property;
    protected $anotherProperty;
    protected $property2;
    protected $booleanProperty = true;
    protected $anotherBooleanProperty = false;

    protected $protectedProperty;

    protected $unexposedProperty = 'unexposed';

    public $publicProperty;
    public $publicProperty2 = 42;

    protected $propertyBag = [];

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setAnotherProperty($anotherProperty)
    {
        $this->anotherProperty = $anotherProperty;
    }

    public function getAnotherProperty()
    {
        return $this->anotherProperty;
    }

    public function getProperty2()
    {
        return $this->property2;
    }
    public function setProperty2($property2)
    {
        $this->property2 = $property2;
    }

    protected function getProtectedProperty()
    {
        return '42';
    }

    protected function setProtectedProperty($value)
    {
        $this->protectedProperty = $value;
    }

    public function isBooleanProperty()
    {
        return 'method called ' . $this->booleanProperty;
    }

    public function setAnotherBooleanProperty($anotherBooleanProperty)
    {
        $this->anotherBooleanProperty = $anotherBooleanProperty;
    }

    public function hasAnotherBooleanProperty()
    {
        return $this->anotherBooleanProperty;
    }

    protected function getPrivateProperty()
    {
        return '21';
    }

    public function setWriteOnlyMagicProperty($value)
    {
    }

    public function has($property)
    {
        return isset($this->propertyBag[$property]);
    }

    public function get($property)
    {
        return isset($this->propertyBag[$property]) ? $this->propertyBag[$property] : null;
    }

    public function set($property, $value)
    {
        $this->propertyBag[$property] = $value;
    }
}

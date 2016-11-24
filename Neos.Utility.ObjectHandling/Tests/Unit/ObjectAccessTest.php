<?php
namespace Neos\Utility\ObjectHandling\Tests\Unit;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\DummyClassWithGettersAndSetters;
use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\Model\EntityWithDoctrineProxy;
use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\ArrayAccessClass;
use Neos\Utility\ObjectAccess;

require_once('Fixture/DummyClassWithGettersAndSetters.php');
require_once('Fixture/ArrayAccessClass.php');
require_once('Fixture/Model/EntityWithDoctrineProxy.php');

/**
 * Testcase for Object Access
 *
 */
class ObjectAccessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DummyClassWithGettersAndSetters
     */
    protected $dummyObject;

    /**
     */
    public function setUp()
    {
        $this->dummyObject = new DummyClassWithGettersAndSetters();
        $this->dummyObject->setProperty('string1');
        $this->dummyObject->setAnotherProperty(42);
        $this->dummyObject->shouldNotBePickedUp = true;
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForGetterProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'property');
        $this->assertEquals($property, 'string1');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForPublicProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
        $this->assertEquals($property, 42, 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnexposedPropertyIfForceDirectAccessIsTrue()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'unexposedProperty', true);
        $this->assertEquals($property, 'unexposed', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnknownPropertyIfForceDirectAccessIsTrue()
    {
        $this->dummyObject->unknownProperty = 'unknown';
        $property = ObjectAccess::getProperty($this->dummyObject, 'unknownProperty', true);
        $this->assertEquals($property, 'unknown', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function getPropertyReturnsPropertyNotAccessibleExceptionForNotExistingPropertyIfForceDirectAccessIsTrue()
    {
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty', true);
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function getPropertyReturnsThrowsExceptionIfPropertyDoesNotExist()
    {
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function getPropertyReturnsThrowsExceptionIfArrayKeyDoesNotExist()
    {
        ObjectAccess::getProperty(array(), 'notExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanIsGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
        $this->assertSame('method called 1', $property);
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanHasGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'anotherBooleanProperty');
        $this->assertSame(false, $property);

        $this->dummyObject->setAnotherBooleanProperty(true);
        $property = ObjectAccess::getProperty($this->dummyObject, 'anotherBooleanProperty');
        $this->assertSame(true, $property);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
    }

    /**
     * @test
     */
    public function setPropertyWorksIfThePropertyNameIsAnInteger()
    {
        $array = new \ArrayObject();
        ObjectAccess::setProperty($array, 42, 'Test');
        $this->assertSame('Test', $array[42]);
    }

    /**
     * @test
     */
    public function setPropertyReturnsFalseIfPropertyIsNotAccessible()
    {
        $this->assertFalse(ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
    }

    /**
     * @test
     */
    public function setPropertySetsValueIfPropertyIsNotAccessibleWhenForceDirectAccessIsTrue()
    {
        $this->assertTrue(ObjectAccess::setProperty($this->dummyObject, 'unexposedProperty', 'was set anyway', true));
        $this->assertAttributeEquals('was set anyway', 'unexposedProperty', $this->dummyObject);
    }

    /**
     * @test
     */
    public function setPropertySetsValueIfPropertyDoesNotExistWhenForceDirectAccessIsTrue()
    {
        $this->assertTrue(ObjectAccess::setProperty($this->dummyObject, 'unknownProperty', 'was set anyway', true));
        $this->assertAttributeEquals('was set anyway', 'unknownProperty', $this->dummyObject);
    }

    /**
     * @test
     */
    public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable()
    {
        ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
        $this->assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
    }

    /**
     * @test
     */
    public function setPropertyWorksWithPublicProperty()
    {
        ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
        $this->assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
    }

    /**
     * @test
     */
    public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray()
    {
        $arrayObject = new \ArrayObject();
        $array = array();

        ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
        ObjectAccess::setProperty($array, 'key', 'value');

        $this->assertEquals(4242, $arrayObject['publicProperty']);
        $this->assertEquals('value', $array['key']);
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArrayObject()
    {
        $arrayObject = new \ArrayObject(array('key' => 'value'));
        $expectedResult = 'value';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'key');
        $this->assertEquals($expectedResult, $actualResult, 'getProperty does not work with ArrayObject property.');
    }

    /**
     * @test
     */
    public function getPropertyCallsCustomGettersOfObjectsImplementingArrayAccess()
    {
        $arrayObject = new \ArrayObject();
        $expectedResult = 'ArrayIterator';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'iteratorClass');
        $this->assertEquals($expectedResult, $actualResult, 'getProperty does not call existing getter of object implementing ArrayAccess.');
    }

    /**
     * @test
     */
    public function getPropertyCallsGettersBeforeCheckingViaArrayAccess()
    {
        $arrayObject = new \ArrayObject(array('iteratorClass' => 'This should be ignored'));
        $expectedResult = 'ArrayIterator';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'iteratorClass');
        $this->assertEquals($expectedResult, $actualResult, 'getProperty does not call existing getter of object implementing ArrayAccess.');
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function getPropertyThrowsExceptionIfArrayObjectDoesNotContainMatchingKeyNorGetter()
    {
        $arrayObject = new \ArrayObject();
        ObjectAccess::getProperty($arrayObject, 'nonExistingProperty');
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function getPropertyDoesNotTryArrayAccessOnSplObjectStorageSubject()
    {
        $splObjectStorage = new \SplObjectStorage();
        ObjectAccess::getProperty($splObjectStorage, 'something');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(array('key' => 'value'));
        $expectedResult = 'value';
        $actualResult = ObjectAccess::getProperty($arrayAccessInstance, 'key');
        $this->assertEquals($expectedResult, $actualResult, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyRespectsForceDirectAccessForArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(array('key' => 'value'));
        $actualResult = ObjectAccess::getProperty($arrayAccessInstance, 'internalProperty', true);
        $this->assertEquals('access through forceDirectAccess', $actualResult, 'getPropertyPath does not respect ForceDirectAccess for ArrayAccess implementations.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArray()
    {
        $array = array('key' => 'value');
        $actualResult = ObjectAccess::getProperty($array, 'key');
        $this->assertEquals('value', $actualResult, 'getProperty does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessNullPropertyOfAnArray()
    {
        $array = array('key' => null);
        $actualResult = ObjectAccess::getProperty($array, 'key');
        $this->assertNull($actualResult, 'getProperty should allow access to NULL properties.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnArray()
    {
        $array = array('parent' => array('key' => 'value'));
        $actualResult = ObjectAccess::getPropertyPath($array, 'parent.key');
        $this->assertEquals('value', $actualResult, 'getPropertyPath does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $array = array('parent' => new \ArrayObject(array('key' => 'value')));
        $actualResult = ObjectAccess::getPropertyPath($array, 'parent.key');
        $this->assertEquals('value', $actualResult, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $expectedPropertyNames = array('anotherBooleanProperty', 'anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2');
        $actualPropertyNames = ObjectAccess::getGettablePropertyNames($this->dummyObject);
        $this->assertEquals($expectedPropertyNames, $actualPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $expectedPropertyNames = array('anotherBooleanProperty', 'anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty');
        $actualPropertyNames = ObjectAccess::getSettablePropertyNames($this->dummyObject);
        $this->assertEquals($expectedPropertyNames, $actualPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;

        $expectedPropertyNames = array('property', 'property2');
        $actualPropertyNames = ObjectAccess::getSettablePropertyNames($stdClassObject);
        $this->assertEquals($expectedPropertyNames, $actualPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties()
    {
        $expectedProperties = array(
            'anotherBooleanProperty' => false,
            'anotherProperty' => 42,
            'booleanProperty' => 'method called 1',
            'property' => 'string1',
            'property2' => null,
            'publicProperty' => null,
            'publicProperty2' => 42);
        $actualProperties = ObjectAccess::getGettableProperties($this->dummyObject);
        $this->assertEquals($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesReturnsPropertiesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;
        $stdClassObject->publicProperty2 = 42;
        $expectedProperties = array(
            'property' => 'string1',
            'property2' => null,
            'publicProperty2' => 42);
        $actualProperties = ObjectAccess::getGettableProperties($stdClassObject);
        $this->assertEquals($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesHandlesDoctrineProxy()
    {
        $proxyObject = new EntityWithDoctrineProxy();

        $expectedProperties = array();
        $actualProperties = ObjectAccess::getGettableProperties($proxyObject);
        $this->assertEquals($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function isPropertySettableTellsIfAPropertyCanBeSet()
    {
        $this->assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
        $this->assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
        $this->assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'property'));

        $this->assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
        $this->assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    /**
     * @test
     */
    public function isPropertySettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';

        $this->assertTrue(ObjectAccess::isPropertySettable($stdClassObject, 'property'));

        $this->assertFalse(ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function isPropertyGettableTellsIfAPropertyCanBeRetrieved()
    {
        $this->assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'publicProperty'));
        $this->assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'property'));
        $this->assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'booleanProperty'));
        $this->assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'anotherBooleanProperty'));

        $this->assertFalse(ObjectAccess::isPropertyGettable($this->dummyObject, 'privateProperty'));
        $this->assertFalse(ObjectAccess::isPropertyGettable($this->dummyObject, 'writeOnlyMagicProperty'));
        $this->assertFalse(ObjectAccess::isPropertyGettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnArrayAccessObjects()
    {
        $arrayObject = new \ArrayObject();
        $arrayObject['key'] = 'v';

        $this->assertTrue(ObjectAccess::isPropertyGettable($arrayObject, 'key'));
        $this->assertFalse(ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';

        $this->assertTrue(ObjectAccess::isPropertyGettable($stdClassObject, 'property'));

        $this->assertFalse(ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty('test');
        $this->dummyObject->setProperty2($alternativeObject);

        $expected = 'test';
        $actual = ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullForNonExistingPropertyPath()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty(new \stdClass());
        $this->dummyObject->setProperty2($alternativeObject);

        $this->assertNull(ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectIsNoObject()
    {
        $string = 'Hello world';

        $this->assertNull(ObjectAccess::getPropertyPath($string, 'property2'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject()
    {
        $object = new \stdClass();
        $object->foo = 'Hello World';

        $this->assertNull(ObjectAccess::getPropertyPath($object, 'foo.bar'));
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function accessorCacheIsNotUsedForStdClass()
    {
        $object1 = new \stdClass();
        $object1->property = 'booh!';
        $object2 = new \stdClass();

        $this->assertEquals('booh!', ObjectAccess::getProperty($object1, 'property'));
        ObjectAccess::getProperty($object2, 'property');
    }
}

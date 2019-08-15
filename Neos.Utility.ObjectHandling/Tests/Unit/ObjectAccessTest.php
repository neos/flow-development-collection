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

use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;
use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\ArrayAccessClass;
use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\DummyClassWithGettersAndSetters;
use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\Model\EntityWithDoctrineProxy;
use Neos\Utility\TypeHandling;

require_once('Fixture/DummyClassWithGettersAndSetters.php');
require_once('Fixture/ArrayAccessClass.php');
require_once('Fixture/Model/EntityWithDoctrineProxy.php');

/**
 * Testcase for Object Access
 *
 */
class ObjectAccessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DummyClassWithGettersAndSetters
     */
    protected $dummyObject;

    /**
     */
    protected function setUp(): void
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
        self::assertEquals($property, 'string1');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForPublicProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
        self::assertEquals($property, 42, 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnexposedPropertyIfForceDirectAccessIsTrue()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'unexposedProperty', true);
        self::assertEquals($property, 'unexposed', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnknownPropertyIfForceDirectAccessIsTrue()
    {
        $this->dummyObject->unknownProperty = 'unknown';
        $property = ObjectAccess::getProperty($this->dummyObject, 'unknownProperty', true);
        self::assertEquals($property, 'unknown', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsPropertyNotAccessibleExceptionForNotExistingPropertyIfForceDirectAccessIsTrue()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty', true);
    }

    /**
     * @test
     */
    public function getPropertyReturnsThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyReturnsThrowsExceptionIfArrayKeyDoesNotExist()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        ObjectAccess::getProperty([], 'notExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanIsGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
        self::assertSame('method called 1', $property);
    }

    /**
     * @test
     */
    public function getPropertyTriesToCallABooleanHasGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'anotherBooleanProperty');
        self::assertSame(false, $property);

        $this->dummyObject->setAnotherBooleanProperty(true);
        $property = ObjectAccess::getProperty($this->dummyObject, 'anotherBooleanProperty');
        self::assertSame(true, $property);
    }

    /**
     * @test
     */
    public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
    }

    /**
     * @test
     */
    public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
    }

    /**
     * @test
     */
    public function setPropertyWorksIfThePropertyNameIsAnInteger()
    {
        $array = new \ArrayObject();
        ObjectAccess::setProperty($array, 42, 'Test');
        self::assertSame('Test', $array[42]);
    }

    /**
     * @test
     */
    public function setPropertyReturnsFalseIfPropertyIsNotAccessible()
    {
        self::assertFalse(ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
    }

    /**
     * @test
     */
    public function setPropertySetsValueIfPropertyIsNotAccessibleWhenForceDirectAccessIsTrue()
    {
        self::assertTrue(ObjectAccess::setProperty($this->dummyObject, 'unexposedProperty', 'was set anyway', true));
        $className = TypeHandling::getTypeForValue($this->dummyObject);
        $propertyReflection = new \ReflectionProperty($className, 'unexposedProperty');
        $propertyReflection->setAccessible(true);
        self::assertEquals('was set anyway', $propertyReflection->getValue($this->dummyObject));
    }

    /**
     * @test
     */
    public function setPropertySetsValueIfPropertyDoesNotExistWhenForceDirectAccessIsTrue()
    {
        self::assertTrue(ObjectAccess::setProperty($this->dummyObject, 'unknownProperty', 'was set anyway', true));
        self::assertEquals('was set anyway', $this->dummyObject->unknownProperty);
    }

    /**
     * @test
     */
    public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable()
    {
        ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
        self::assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
    }

    /**
     * @test
     */
    public function setPropertyWorksWithPublicProperty()
    {
        ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
        self::assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
    }

    /**
     * @test
     */
    public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray()
    {
        $arrayObject = new \ArrayObject();
        $array = [];

        ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
        ObjectAccess::setProperty($array, 'key', 'value');

        self::assertEquals(4242, $arrayObject['publicProperty']);
        self::assertEquals('value', $array['key']);
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArrayObject()
    {
        $arrayObject = new \ArrayObject(['key' => 'value']);
        $expectedResult = 'value';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'key');
        self::assertEquals($expectedResult, $actualResult, 'getProperty does not work with ArrayObject property.');
    }

    /**
     * @test
     */
    public function getPropertyCallsCustomGettersOfObjectsImplementingArrayAccess()
    {
        $arrayObject = new \ArrayObject();
        $expectedResult = 'ArrayIterator';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'iteratorClass');
        self::assertEquals($expectedResult, $actualResult, 'getProperty does not call existing getter of object implementing ArrayAccess.');
    }

    /**
     * @test
     */
    public function getPropertyCallsGettersBeforeCheckingViaArrayAccess()
    {
        $arrayObject = new \ArrayObject(['iteratorClass' => 'This should be ignored']);
        $expectedResult = 'ArrayIterator';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'iteratorClass');
        self::assertEquals($expectedResult, $actualResult, 'getProperty does not call existing getter of object implementing ArrayAccess.');
    }

    /**
     * @test
     */
    public function getPropertyThrowsExceptionIfArrayObjectDoesNotContainMatchingKeyNorGetter()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $arrayObject = new \ArrayObject();
        ObjectAccess::getProperty($arrayObject, 'nonExistingProperty');
    }

    /**
     * @test
     */
    public function getPropertyDoesNotTryArrayAccessOnSplObjectStorageSubject()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $splObjectStorage = new \SplObjectStorage();
        ObjectAccess::getProperty($splObjectStorage, 'something');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(['key' => 'value']);
        $expectedResult = 'value';
        $actualResult = ObjectAccess::getProperty($arrayAccessInstance, 'key');
        self::assertEquals($expectedResult, $actualResult, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getPropertyRespectsForceDirectAccessForArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(['key' => 'value']);
        $actualResult = ObjectAccess::getProperty($arrayAccessInstance, 'internalProperty', true);
        self::assertEquals('access through forceDirectAccess', $actualResult, 'getPropertyPath does not respect ForceDirectAccess for ArrayAccess implementations.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessPropertiesOfAnArray()
    {
        $array = ['key' => 'value'];
        $actualResult = ObjectAccess::getProperty($array, 'key');
        self::assertEquals('value', $actualResult, 'getProperty does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyCanAccessNullPropertyOfAnArray()
    {
        $array = ['key' => null];
        $actualResult = ObjectAccess::getProperty($array, 'key');
        self::assertNull($actualResult, 'getProperty should allow access to NULL properties.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnArray()
    {
        $array = ['parent' => ['key' => 'value']];
        $actualResult = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actualResult, 'getPropertyPath does not work with Array property.');
    }

    /**
     * @test
     */
    public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $array = ['parent' => new \ArrayObject(['key' => 'value'])];
        $actualResult = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actualResult, 'getPropertyPath does not work with Array Access property.');
    }

    /**
     * @test
     */
    public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2'];
        $actualPropertyNames = ObjectAccess::getGettablePropertyNames($this->dummyObject);
        self::assertEquals($expectedPropertyNames, $actualPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty'];
        $actualPropertyNames = ObjectAccess::getSettablePropertyNames($this->dummyObject);
        self::assertEquals($expectedPropertyNames, $actualPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;

        $expectedPropertyNames = ['property', 'property2'];
        $actualPropertyNames = ObjectAccess::getSettablePropertyNames($stdClassObject);
        self::assertEquals($expectedPropertyNames, $actualPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesReturnsTheCorrectValuesForAllProperties()
    {
        $expectedProperties = [
            'anotherBooleanProperty' => false,
            'anotherProperty' => 42,
            'booleanProperty' => 'method called 1',
            'property' => 'string1',
            'property2' => null,
            'publicProperty' => null,
            'publicProperty2' => 42
        ];
        $actualProperties = ObjectAccess::getGettableProperties($this->dummyObject);
        self::assertEquals($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
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
        $expectedProperties = [
            'property' => 'string1',
            'property2' => null,
            'publicProperty2' => 42
        ];
        $actualProperties = ObjectAccess::getGettableProperties($stdClassObject);
        self::assertEquals($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function getGettablePropertiesHandlesDoctrineProxy()
    {
        $proxyObject = new EntityWithDoctrineProxy();

        $expectedProperties = [];
        $actualProperties = ObjectAccess::getGettableProperties($proxyObject);
        self::assertEquals($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
    }

    /**
     * @test
     */
    public function isPropertySettableTellsIfAPropertyCanBeSet()
    {
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'property'));

        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    /**
     * @test
     */
    public function isPropertySettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';

        self::assertTrue(ObjectAccess::isPropertySettable($stdClassObject, 'property'));

        self::assertFalse(ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
    }

    /**
     * @test
     */
    public function isPropertyGettableTellsIfAPropertyCanBeRetrieved()
    {
        self::assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'publicProperty'));
        self::assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'property'));
        self::assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'booleanProperty'));
        self::assertTrue(ObjectAccess::isPropertyGettable($this->dummyObject, 'anotherBooleanProperty'));

        self::assertFalse(ObjectAccess::isPropertyGettable($this->dummyObject, 'privateProperty'));
        self::assertFalse(ObjectAccess::isPropertyGettable($this->dummyObject, 'writeOnlyMagicProperty'));
        self::assertFalse(ObjectAccess::isPropertyGettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnArrayAccessObjects()
    {
        $arrayObject = new \ArrayObject();
        $arrayObject['key'] = 'v';

        self::assertTrue(ObjectAccess::isPropertyGettable($arrayObject, 'key'));
        self::assertFalse(ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
    }

    /**
     * @test
     */
    public function isPropertyGettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';

        self::assertTrue(ObjectAccess::isPropertyGettable($stdClassObject, 'property'));

        self::assertFalse(ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
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
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullForNonExistingPropertyPath()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty(new \stdClass());
        $this->dummyObject->setProperty2($alternativeObject);

        self::assertNull(ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectIsNoObject()
    {
        $string = 'Hello world';

        self::assertNull(ObjectAccess::getPropertyPath($string, 'property2'));
    }

    /**
     * @test
     */
    public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject()
    {
        $object = new \stdClass();
        $object->foo = 'Hello World';

        self::assertNull(ObjectAccess::getPropertyPath($object, 'foo.bar'));
    }

    /**
     * @test
     */
    public function accessorCacheIsNotUsedForStdClass()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $object1 = new \stdClass();
        $object1->property = 'booh!';
        $object2 = new \stdClass();

        self::assertEquals('booh!', ObjectAccess::getProperty($object1, 'property'));
        ObjectAccess::getProperty($object2, 'property');
    }
}

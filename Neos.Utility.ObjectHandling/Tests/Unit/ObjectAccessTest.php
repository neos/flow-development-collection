<?php

declare(strict_types=1);

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
use Neos\Utility\ObjectHandling\Tests\Unit\Fixture\ProxiedClassWithPrivateProperty;
use Neos\Utility\TypeHandling;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once('Fixture/DummyClassWithGettersAndSetters.php');
require_once('Fixture/ArrayAccessClass.php');
require_once('Fixture/Model/EntityWithDoctrineProxy.php');
require_once('Fixture/ProxiedClassWithPrivateProperty.php');

/**
 * Testcase for Object Access
 *
 */
final class ObjectAccessTest extends TestCase
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

    #[Test]
    public function getPropertyReturnsExpectedValueForGetterProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'property');
        self::assertEquals('string1', $property);
    }

    #[Test]
    public function getPropertyReturnsExpectedValueForPublicProperty()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
        self::assertEquals(42, $property, 'A property of a given object was not returned correctly.');
    }

    #[Test]
    public function getPropertyReturnsExpectedValueForUnexposedPropertyIfForceDirectAccessIsTrue()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'unexposedProperty', true);
        self::assertEquals('unexposed', $property, 'A property of a given object was not returned correctly.');
    }

    #[Test]
    public function getPropertyReturnsExpectedValueForUnknownPropertyIfForceDirectAccessIsTrue()
    {
        $this->dummyObject->unknownProperty = 'unknown';
        $property = ObjectAccess::getProperty($this->dummyObject, 'unknownProperty', true);
        self::assertEquals('unknown', $property, 'A property of a given object was not returned correctly.');
    }

    #[Test]
    public function getPropertyReturnsPropertyNotAccessibleExceptionForNotExistingPropertyIfForceDirectAccessIsTrue()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty', true);
    }

    #[Test]
    public function getPropertyReturnsThrowsExceptionIfPropertyDoesNotExist()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
    }

    #[Test]
    public function getPropertyReturnsThrowsExceptionIfArrayKeyDoesNotExist()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        ObjectAccess::getProperty([], 'notExistingProperty');
    }

    #[Test]
    public function getPropertyTriesToCallABooleanIsGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'booleanProperty');
        self::assertSame('method called 1', $property);
    }

    #[Test]
    public function getPropertyTriesToCallABooleanHasGetterMethodIfItExists()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'anotherBooleanProperty');
        self::assertFalse($property);

        $this->dummyObject->setAnotherBooleanProperty(true);
        $property = ObjectAccess::getProperty($this->dummyObject, 'anotherBooleanProperty');
        self::assertTrue($property);
    }

    #[Test]
    public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
    }

    #[Test]
    public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
    }

    #[Test]
    public function setPropertyWorksIfThePropertyNameIsAnInteger()
    {
        $array = new \ArrayObject();
        ObjectAccess::setProperty($array, 42, 'Test');
        self::assertSame('Test', $array[42]);
    }

    #[Test]
    public function setPropertyReturnsFalseIfPropertyIsNotAccessible()
    {
        self::assertFalse(ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
    }

    #[Test]
    public function setPropertySetsValueIfPropertyIsNotAccessibleWhenForceDirectAccessIsTrue()
    {
        self::assertTrue(ObjectAccess::setProperty($this->dummyObject, 'unexposedProperty', 'was set anyway', true));
        $className = TypeHandling::getTypeForValue($this->dummyObject);
        $propertyReflection = new \ReflectionProperty($className, 'unexposedProperty');
        $propertyReflection->setAccessible(true);
        self::assertEquals('was set anyway', $propertyReflection->getValue($this->dummyObject));
    }

    #[Test]
    public function setPropertySetsValueIfPropertyDoesNotExistWhenForceDirectAccessIsTrue()
    {
        self::assertTrue(ObjectAccess::setProperty($this->dummyObject, 'unknownProperty', 'was set anyway', true));
        self::assertEquals('was set anyway', $this->dummyObject->unknownProperty);
    }

    #[Test]
    public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable()
    {
        ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
        self::assertEquals(4242, $this->dummyObject->getProperty(), 'setProperty does not work with setter.');
    }

    #[Test]
    public function setPropertyWorksWithPublicProperty()
    {
        ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
        self::assertEquals(4242, $this->dummyObject->publicProperty, 'setProperty does not work with public property.');
    }

    #[Test]
    public function setPropertyCanDirectlySetValuesInAnArrayObjectOrArray()
    {
        $arrayObject = new \ArrayObject();
        $array = [];

        ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
        ObjectAccess::setProperty($array, 'key', 'value');

        self::assertEquals(4242, $arrayObject['publicProperty']);
        self::assertEquals('value', $array['key']);
    }

    #[Test]
    public function getPropertyCanAccessPropertiesOfAnArrayObject()
    {
        $arrayObject = new \ArrayObject(['key' => 'value']);
        $expectedResult = 'value';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'key');
        self::assertEquals($expectedResult, $actualResult, 'getProperty does not work with ArrayObject property.');
    }

    #[Test]
    public function getPropertyCallsCustomGettersOfObjectsImplementingArrayAccess()
    {
        $arrayObject = new \ArrayObject();
        $expectedResult = 'ArrayIterator';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'iteratorClass');
        self::assertEquals($expectedResult, $actualResult, 'getProperty does not call existing getter of object implementing ArrayAccess.');
    }

    #[Test]
    public function getPropertyCallsGettersBeforeCheckingViaArrayAccess()
    {
        $arrayObject = new \ArrayObject(['iteratorClass' => 'This should be ignored']);
        $expectedResult = 'ArrayIterator';
        $actualResult = ObjectAccess::getProperty($arrayObject, 'iteratorClass');
        self::assertEquals($expectedResult, $actualResult, 'getProperty does not call existing getter of object implementing ArrayAccess.');
    }

    #[Test]
    public function getPropertyThrowsExceptionIfArrayObjectDoesNotContainMatchingKeyNorGetter()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $arrayObject = new \ArrayObject();
        ObjectAccess::getProperty($arrayObject, 'nonExistingProperty');
    }

    #[Test]
    public function getPropertyDoesNotTryArrayAccessOnSplObjectStorageSubject()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $splObjectStorage = new \SplObjectStorage();
        ObjectAccess::getProperty($splObjectStorage, 'something');
    }

    #[Test]
    public function getPropertyCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(['key' => 'value']);
        $expectedResult = 'value';
        $actualResult = ObjectAccess::getProperty($arrayAccessInstance, 'key');
        self::assertEquals($expectedResult, $actualResult, 'getPropertyPath does not work with Array Access property.');
    }

    #[Test]
    public function getPropertyRespectsForceDirectAccessForArrayAccess()
    {
        $arrayAccessInstance = new ArrayAccessClass(['key' => 'value']);
        $actualResult = ObjectAccess::getProperty($arrayAccessInstance, 'internalProperty', true);
        self::assertEquals('access through forceDirectAccess', $actualResult, 'getPropertyPath does not respect ForceDirectAccess for ArrayAccess implementations.');
    }

    #[Test]
    public function getPropertyCanAccessPropertiesOfAnArray()
    {
        $array = ['key' => 'value'];
        $actualResult = ObjectAccess::getProperty($array, 'key');
        self::assertEquals('value', $actualResult, 'getProperty does not work with Array property.');
    }

    #[Test]
    public function getPropertyCanAccessNullPropertyOfAnArray()
    {
        $array = ['key' => null];
        $actualResult = ObjectAccess::getProperty($array, 'key');
        self::assertNull($actualResult, 'getProperty should allow access to NULL properties.');
    }

    #[Test]
    public function getPropertyPathCanAccessPropertiesOfAnArray()
    {
        $array = ['parent' => ['key' => 'value']];
        $actualResult = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actualResult, 'getPropertyPath does not work with Array property.');
    }

    #[Test]
    public function getPropertyPathCanAccessPropertiesOfAnObjectImplementingArrayAccess()
    {
        $array = ['parent' => new \ArrayObject(['key' => 'value'])];
        $actualResult = ObjectAccess::getPropertyPath($array, 'parent.key');
        self::assertEquals('value', $actualResult, 'getPropertyPath does not work with Array Access property.');
    }

    #[Test]
    public function getGettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'booleanProperty', 'property', 'property2', 'publicProperty', 'publicProperty2'];
        $actualPropertyNames = ObjectAccess::getGettablePropertyNames($this->dummyObject);
        self::assertSame($expectedPropertyNames, $actualPropertyNames, 'getGettablePropertyNames returns not all gettable properties.');
    }

    #[Test]
    public function getSettablePropertyNamesReturnsAllPropertiesWhichAreAvailable()
    {
        $expectedPropertyNames = ['anotherBooleanProperty', 'anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2', 'writeOnlyMagicProperty'];
        $actualPropertyNames = ObjectAccess::getSettablePropertyNames($this->dummyObject);
        self::assertSame($expectedPropertyNames, $actualPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    #[Test]
    public function getSettablePropertyNamesReturnsPropertyNamesOfStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'string1';
        $stdClassObject->property2 = null;

        $expectedPropertyNames = ['property', 'property2'];
        $actualPropertyNames = ObjectAccess::getSettablePropertyNames($stdClassObject);
        self::assertSame($expectedPropertyNames, $actualPropertyNames, 'getSettablePropertyNames returns not all settable properties.');
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function getGettablePropertiesHandlesDoctrineProxy()
    {
        $proxyObject = new EntityWithDoctrineProxy();

        $expectedProperties = [];
        $actualProperties = ObjectAccess::getGettableProperties($proxyObject);
        self::assertSame($expectedProperties, $actualProperties, 'expectedProperties did not return the right values for the properties.');
    }

    #[Test]
    public function isPropertySettableTellsIfAPropertyCanBeSet()
    {
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'writeOnlyMagicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'publicProperty'));
        self::assertTrue(ObjectAccess::isPropertySettable($this->dummyObject, 'property'));

        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'privateProperty'));
        self::assertFalse(ObjectAccess::isPropertySettable($this->dummyObject, 'shouldNotBePickedUp'));
    }

    #[Test]
    public function isPropertySettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';

        self::assertTrue(ObjectAccess::isPropertySettable($stdClassObject, 'property'));

        self::assertFalse(ObjectAccess::isPropertySettable($stdClassObject, 'undefinedProperty'));
    }

    #[Test]
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

    #[Test]
    public function isPropertyGettableWorksOnArrayAccessObjects()
    {
        $arrayObject = new \ArrayObject();
        $arrayObject['key'] = 'v';

        self::assertTrue(ObjectAccess::isPropertyGettable($arrayObject, 'key'));
        self::assertFalse(ObjectAccess::isPropertyGettable($arrayObject, 'undefinedKey'));
    }

    #[Test]
    public function isPropertyGettableWorksOnStdClass()
    {
        $stdClassObject = new \stdClass();
        $stdClassObject->property = 'foo';

        self::assertTrue(ObjectAccess::isPropertyGettable($stdClassObject, 'property'));

        self::assertFalse(ObjectAccess::isPropertyGettable($stdClassObject, 'undefinedProperty'));
    }

    #[Test]
    public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty('test');
        $this->dummyObject->setProperty2($alternativeObject);

        $expected = 'test';
        $actual = ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property');
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function getPropertyPathReturnsNullForNonExistingPropertyPath()
    {
        $alternativeObject = new DummyClassWithGettersAndSetters();
        $alternativeObject->setProperty(new \stdClass());
        $this->dummyObject->setProperty2($alternativeObject);

        self::assertNull(ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property.not.existing'));
    }

    #[Test]
    public function getPropertyPathReturnsNullIfSubjectIsNoObject()
    {
        $string = 'Hello world';

        self::assertNull(ObjectAccess::getPropertyPath($string, 'property2'));
    }

    #[Test]
    public function getPropertyPathReturnsNullIfSubjectOnPathIsNoObject()
    {
        $object = new \stdClass();
        $object->foo = 'Hello World';

        self::assertNull(ObjectAccess::getPropertyPath($object, 'foo.bar'));
    }

    #[Test]
    public function accessorCacheIsNotUsedForStdClass()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $object1 = new \stdClass();
        $object1->property = 'booh!';
        $object2 = new \stdClass();

        self::assertEquals('booh!', ObjectAccess::getProperty($object1, 'property'));
        ObjectAccess::getProperty($object2, 'property');
    }

    #[Test]
    public function getPropertyUsingDirectAccessWorksOnPrivatePropertyOfProxyParent()
    {
        $proxyObject = new ProxiedClassWithPrivateProperty();

        self::assertEquals('original', ObjectAccess::getProperty($proxyObject, 'property', true));
    }

    #[Test]
    public function setPropertyUsingDirectAccessWorksOnPrivatePropertyOfProxyParent()
    {
        $proxyObject = new ProxiedClassWithPrivateProperty();

        ObjectAccess::setProperty($proxyObject, 'property', 'changed', true);
        self::assertEquals('changed', $proxyObject->getProperty());
    }
}

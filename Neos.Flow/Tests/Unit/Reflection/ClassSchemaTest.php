<?php
namespace Neos\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\Exception\ClassSchemaConstraintViolationException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Class Schema.
 *
 * Note that many parts of the class schema functionality are already tested by the class
 * schema builder testcase.
 */
class ClassSchemaTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hasPropertyReturnsTrueOnlyForExistingProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'string');
        $classSchema->addProperty('b', 'integer');

        self::assertTrue($classSchema->hasProperty('a'));
        self::assertTrue($classSchema->hasProperty('b'));
        self::assertFalse($classSchema->hasProperty('c'));
    }

    /**
     * @test
     */
    public function getPropertiesReturnsAddedProperties()
    {
        $expectedProperties = [
            'a' => ['type' => 'string', 'elementType' => null, 'lazy' => false, 'transient' => false],
            'b' => ['type' => 'Neos\Flow\SomeObject', 'elementType' => null, 'lazy' => true, 'transient' => false],
            'c' => ['type' => 'Neos\Flow\SomeOtherObject', 'elementType' => null, 'lazy' => true, 'transient' => true]
        ];

        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'string');
        $classSchema->addProperty('b', 'Neos\Flow\SomeObject', true);
        $classSchema->addProperty('c', 'Neos\Flow\SomeOtherObject', true, true);

        self::assertSame($expectedProperties, $classSchema->getProperties());
    }

    /**
     * @test
     */
    public function isPropertyLazyReturnsAttributeForAddedProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'Neos\Flow\SomeObject');
        $classSchema->addProperty('b', 'Neos\Flow\SomeObject', true);

        self::assertFalse($classSchema->isPropertyLazy('a'));
        self::assertTrue($classSchema->isPropertyLazy('b'));
    }

    /**
     * @test
     */
    public function isPropertyTransientReturnsAttributeForAddedProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'Neos\Flow\SomeObject');
        $classSchema->addProperty('b', 'Neos\Flow\SomeObject', false, true);

        self::assertFalse($classSchema->isPropertyTransient('a'));
        self::assertTrue($classSchema->isPropertyTransient('b'));
    }

    /**
     * @test
     */
    public function markAsIdentityPropertyRejectsUnknownProperties()
    {
        $this->expectException(\InvalidArgumentException::class);
        $classSchema = new ClassSchema('SomeClass');

        $classSchema->markAsIdentityProperty('unknownProperty');
    }

    /**
     * @test
     */
    public function markAsIdentityPropertyRejectsLazyLoadedProperties()
    {
        $this->expectException(\InvalidArgumentException::class);
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('lazyProperty', 'Neos\Flow\SomeObject', true);

        $classSchema->markAsIdentityProperty('lazyProperty');
    }

    /**
     * @test
     */
    public function getIdentityPropertiesReturnsNamesAndTypes()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'string');
        $classSchema->addProperty('b', 'integer');

        $classSchema->markAsIdentityProperty('a');

        self::assertSame(['a' => 'string'], $classSchema->getIdentityProperties());
    }

    /**
     * data provider for addPropertyAcceptsValidPropertyTypes
     */
    public function validPropertyTypes()
    {
        return [
            ['integer'],
            ['int'],
            ['float'],
            ['boolean'],
            ['bool'],
            ['string'],
            ['DateTime'],
            ['array'],
            ['ArrayObject'],
            ['SplObjectStorage'],
            ['Neos\Flow\Foo'],
            ['\Neos\Flow\Bar'],
            ['\Some\Object'],
            ['SomeObject'],
            ['array<string>'],
            ['array<Neos\Flow\Baz>']
        ];
    }

    /**
     * @dataProvider validPropertyTypes()
     * @test
     * @doesNotPerformAssertions
     */
    public function addPropertyAcceptsValidPropertyTypes($propertyType)
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', $propertyType);
    }

    /**
     * data provider for addPropertyRejectsInvalidPropertyTypes
     */
    public function invalidPropertyTypes()
    {
        return [
            ['string<string>'],
            ['int<Neos\Flow\Baz>']
        ];
    }

    /**
     * @dataProvider invalidPropertyTypes()
     * @test
     */
    public function addPropertyRejectsInvalidPropertyTypes($propertyType)
    {
        $this->expectException(\InvalidArgumentException::class);
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', $propertyType);
    }

    /**
     * Collections are arrays, ArrayObject and SplObjectStorage
     *
     * @test
     */
    public function addPropertyStoresElementTypesForCollectionProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'array<\Neos\Flow\Foo>');

        $properties = $classSchema->getProperties();
        self::assertEquals('array', $properties['a']['type']);
        self::assertEquals('Neos\Flow\Foo', $properties['a']['elementType']);
    }

    /**
     * @test
     */
    public function markAsIdentityPropertyThrowsExceptionForValueObjects()
    {
        $this->expectException(ClassSchemaConstraintViolationException::class);
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->setModelType(ClassSchema::MODELTYPE_VALUEOBJECT);
        $classSchema->markAsIdentityProperty('foo');
    }

    /**
     * @test
     */
    public function setModelTypeResetsIdentityPropertiesAndAggregateRootForValueObjects()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->setModelType(ClassSchema::MODELTYPE_ENTITY);
        $classSchema->addProperty('foo', 'string');
        $classSchema->addProperty('bar', 'string');
        $classSchema->markAsIdentityProperty('bar');
        $classSchema->setRepositoryClassName('Some\Repository');
        self::assertSame(['bar' => 'string'], $classSchema->getIdentityProperties());

        $classSchema->setModelType(ClassSchema::MODELTYPE_VALUEOBJECT);

        self::assertSame([], $classSchema->getIdentityProperties());
        self::assertFalse($classSchema->isAggregateRoot());
    }

    /**
     * @return array
     */
    public function collectionTypes()
    {
        return [
            ['array'],
            ['SplObjectStorage'],
            ['Doctrine\Common\Collections\Collection'],
            ['Doctrine\Common\Collections\ArrayCollection']
        ];
    }

    /**
     * @test
     * @dataProvider collectionTypes
     * @param string $type
     */
    public function isMultiValuedPropertyReturnsTrueForCollectionTypes($type)
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('testProperty', $type);
        self::assertTrue($classSchema->isMultiValuedProperty('testProperty'));
    }
}

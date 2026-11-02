<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Class Schema.
 *
 * Note that many parts of the class schema functionality are already tested by the class
 * schema builder testcase.
 */
final class ClassSchemaTest extends UnitTestCase
{
    #[Test]
    public function hasPropertyReturnsTrueOnlyForExistingProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'string');
        $classSchema->addProperty('b', 'integer');

        self::assertTrue($classSchema->hasProperty('a'));
        self::assertTrue($classSchema->hasProperty('b'));
        self::assertFalse($classSchema->hasProperty('c'));
    }

    #[Test]
    public function getPropertiesReturnsAddedProperties()
    {
        $expectedProperties = [
            'a' => ['type' => 'string', 'elementType' => null, 'nullable' => false, 'lazy' => false, 'transient' => false],
            'b' => ['type' => 'Neos\Flow\SomeObject', 'elementType' => null, 'nullable' => false, 'lazy' => true, 'transient' => false],
            'c' => ['type' => 'Neos\Flow\SomeOtherObject', 'elementType' => null, 'nullable' => false, 'lazy' => true, 'transient' => true]
        ];

        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'string');
        $classSchema->addProperty('b', 'Neos\Flow\SomeObject', true);
        $classSchema->addProperty('c', 'Neos\Flow\SomeOtherObject', true, true);

        self::assertSame($expectedProperties, $classSchema->getProperties());
    }

    #[Test]
    public function isPropertyLazyReturnsAttributeForAddedProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'Neos\Flow\SomeObject');
        $classSchema->addProperty('b', 'Neos\Flow\SomeObject', true);

        self::assertFalse($classSchema->isPropertyLazy('a'));
        self::assertTrue($classSchema->isPropertyLazy('b'));
    }

    #[Test]
    public function isPropertyTransientReturnsAttributeForAddedProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'Neos\Flow\SomeObject');
        $classSchema->addProperty('b', 'Neos\Flow\SomeObject', false, true);

        self::assertFalse($classSchema->isPropertyTransient('a'));
        self::assertTrue($classSchema->isPropertyTransient('b'));
    }

    #[Test]
    public function markAsIdentityPropertyRejectsUnknownProperties()
    {
        $this->expectException(\InvalidArgumentException::class);
        $classSchema = new ClassSchema('SomeClass');

        $classSchema->markAsIdentityProperty('unknownProperty');
    }

    #[Test]
    public function markAsIdentityPropertyRejectsLazyLoadedProperties()
    {
        $this->expectException(\InvalidArgumentException::class);
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('lazyProperty', 'Neos\Flow\SomeObject', true);

        $classSchema->markAsIdentityProperty('lazyProperty');
    }

    #[Test]
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
    public static function validPropertyTypes(): \Iterator
    {
        yield ['integer'];
        yield ['int'];
        yield ['float'];
        yield ['boolean'];
        yield ['bool'];
        yield ['string'];
        yield ['DateTime'];
        yield ['array'];
        yield ['ArrayObject'];
        yield ['SplObjectStorage'];
        yield ['Neos\Flow\Foo'];
        yield ['\Neos\Flow\Bar'];
        yield ['\Some\Object'];
        yield ['SomeObject'];
        yield ['array<string>'];
        yield ['array<Neos\Flow\Baz>'];
        yield ['?string'];
        yield ['null|string'];
        yield ['string|null'];
        yield ['?\Some\Object'];
        yield ['\Some\Object|null'];
        yield ['?array<Neos\Flow\Baz>'];
        yield ['null|array<Neos\Flow\Baz>'];
    }

    #[DataProvider('validPropertyTypes')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function addPropertyAcceptsValidPropertyTypes($propertyType)
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', $propertyType);
    }

    /**
     * data provider for addPropertyRejectsInvalidPropertyTypes
     */
    public static function invalidPropertyTypes(): \Iterator
    {
        yield ['string<string>'];
        yield ['int<Neos\Flow\Baz>'];
    }

    #[DataProvider('invalidPropertyTypes')]
    #[Test]
    public function addPropertyRejectsInvalidPropertyTypes($propertyType)
    {
        $this->expectException(\InvalidArgumentException::class);
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', $propertyType);
    }

    /**
     * Collections are arrays, ArrayObject and SplObjectStorage
     */
    #[Test]
    public function addPropertyStoresElementTypesForCollectionProperties()
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('a', 'array<\Neos\Flow\Foo>');

        $properties = $classSchema->getProperties();
        self::assertEquals('array', $properties['a']['type']);
        self::assertEquals('Neos\Flow\Foo', $properties['a']['elementType']);
    }

    #[Test]
    public function markAsIdentityPropertyThrowsExceptionForValueObjects()
    {
        $this->expectException(ClassSchemaConstraintViolationException::class);
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->setModelType(ClassSchema::MODELTYPE_VALUEOBJECT);
        $classSchema->markAsIdentityProperty('foo');
    }

    #[Test]
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
     * @return \Iterator<(int | string), mixed>
     */
    public static function collectionTypes(): \Iterator
    {
        yield ['array'];
        yield ['SplObjectStorage'];
        yield ['Doctrine\Common\Collections\Collection'];
        yield ['Doctrine\Common\Collections\ArrayCollection'];
    }

    /**
     * @param string $type
     */
    #[DataProvider('collectionTypes')]
    #[Test]
    public function isMultiValuedPropertyReturnsTrueForCollectionTypes($type)
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('testProperty', $type);
        self::assertTrue($classSchema->isMultiValuedProperty('testProperty'));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function nullableTypes(): \Iterator
    {
        yield ['?string'];
        yield ['integer|null'];
        yield ['null|array'];
        yield ['Doctrine\Common\Collections\ArrayCollection|null'];
    }

    /**
     * @param string $type
     */
    #[DataProvider('nullableTypes')]
    #[Test]
    public function correctlyReturnsNullabilityForProperties($type)
    {
        $classSchema = new ClassSchema('SomeClass');
        $classSchema->addProperty('testProperty', $type);
        self::assertTrue($classSchema->isPropertyNullable('testProperty'));
    }
}

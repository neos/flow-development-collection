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
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the Utility\TypeHandling class
 */
final class TypeHandlingTest extends TestCase
{
    #[Test]
    public function parseTypeThrowsExceptionOnInvalidType()
    {
        $this->expectException(InvalidTypeException::class);
        TypeHandling::parseType('$something');
    }

    #[Test]
    public function parseTypeThrowsExceptionOnInvalidElementTypeHint()
    {
        $this->expectException(InvalidTypeException::class);
        TypeHandling::parseType('string<integer>');
    }

    /**
     * data provider for parseTypeReturnsArrayWithInformation
     */
    public static function types(): \Iterator
    {
        yield ['null', ['type' => 'null', 'elementType' => null, 'nullable' => true]];
        yield ['int', ['type' => 'integer', 'elementType' => null, 'nullable' => false]];
        yield ['string', ['type' => 'string', 'elementType' => null, 'nullable' => false]];
        yield ['DateTime', ['type' => 'DateTime', 'elementType' => null, 'nullable' => false]];
        yield ['DateTimeImmutable', ['type' => 'DateTimeImmutable', 'elementType' => null, 'nullable' => false]];
        yield ['Neos\Foo\Bar', ['type' => 'Neos\Foo\Bar', 'elementType' => null, 'nullable' => false]];
        yield ['\Neos\Foo\Bar', ['type' => 'Neos\Foo\Bar', 'elementType' => null, 'nullable' => false]];
        yield ['\stdClass', ['type' => 'stdClass', 'elementType' => null, 'nullable' => false]];
        yield ['array<integer>', ['type' => 'array', 'elementType' => 'integer', 'nullable' => false]];
        yield ['ArrayObject<string>', ['type' => 'ArrayObject', 'elementType' => 'string', 'nullable' => false]];
        yield ['SplObjectStorage<Neos\Foo\Bar>', ['type' => 'SplObjectStorage', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]];
        yield ['SplObjectStorage<\Neos\Foo\Bar>', ['type' => 'SplObjectStorage', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]];
        yield ['Doctrine\Common\Collections\Collection<\Neos\Foo\Bar>', ['type' => 'Doctrine\Common\Collections\Collection', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]];
        yield ['Doctrine\Common\Collections\ArrayCollection<\Neos\Foo\Bar>', ['type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]];
        yield ['\SomeClass with appendix', ['type' => 'SomeClass', 'elementType' => null, 'nullable' => false]];
        // Types might also contain underscores at various points.
        yield ['Doctrine\Common\Collections\Special_Class_With_Underscores', ['type' => 'Doctrine\Common\Collections\Special_Class_With_Underscores', 'elementType' => null, 'nullable' => false]];
        yield ['Doctrine\Common\Collections\ArrayCollection<\Neos\Foo_\Bar>', ['type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'Neos\Foo_\Bar', 'nullable' => false]];
    }

    #[DataProvider('types')]
    #[Test]
    public function parseTypeReturnsArrayWithInformation(string $type, array $expectedResult)
    {
        self::assertEquals(
            $expectedResult,
            TypeHandling::parseType($type),
            'Failed for ' . $type
        );
    }

    /**
     * data provider for extractCollectionTypeReturnsOnlyTheMainType
     */
    public static function compositeTypes(): \Iterator
    {
        yield ['integer', 'integer'];
        yield ['int', 'int'];
        yield ['array', 'array'];
        yield ['ArrayObject', 'ArrayObject'];
        yield ['SplObjectStorage', 'SplObjectStorage'];
        yield ['Doctrine\Common\Collections\Collection', 'Doctrine\Common\Collections\Collection'];
        yield ['Doctrine\Common\Collections\ArrayCollection', 'Doctrine\Common\Collections\ArrayCollection'];
        yield ['array<\Some\Other\Class>', 'array'];
        yield ['ArrayObject<int>', 'ArrayObject'];
        yield ['SplObjectStorage<\object>', 'SplObjectStorage'];
        yield ['Doctrine\Common\Collections\Collection<ElementType>', 'Doctrine\Common\Collections\Collection'];
        yield ['Doctrine\Common\Collections\ArrayCollection<>', 'Doctrine\Common\Collections\ArrayCollection'];
        // Types might also contain underscores at various points.
        yield ['Doctrine\Common\Collections\Array_Collection<>', 'Doctrine\Common\Collections\Array_Collection'];
    }

    #[DataProvider('compositeTypes')]
    #[Test]
    public function extractCollectionTypeReturnsOnlyTheMainType(string $type, string $expectedResult)
    {
        self::assertSame(
            $expectedResult,
            TypeHandling::truncateElementType($type),
            'Failed for ' . $type
        );
    }

    /**
     * data provider for normalizeTypesReturnsNormalizedType
     */
    public static function normalizeTypes(): \Iterator
    {
        yield ['int', 'integer'];
        yield ['double', 'float'];
        yield ['bool', 'boolean'];
        yield ['string', 'string'];
    }

    #[DataProvider('normalizeTypes')]
    #[Test]
    public function normalizeTypesReturnsNormalizedType(string $type, string $normalized)
    {
        self::assertSame(TypeHandling::normalizeType($type), $normalized);
    }

    /**
     * data provider for isLiteralReturnsFalseForNonLiteralTypes
     */
    public static function nonLiteralTypes(): \Iterator
    {
        yield ['null'];
        yield ['DateTime'];
        yield ['\Foo\Bar'];
        yield ['array'];
        yield ['ArrayObject'];
        yield ['stdClass'];
    }

    #[DataProvider('nonliteralTypes')]
    #[Test]
    public function isLiteralReturnsFalseForNonLiteralTypes(string $type)
    {
        self::assertFalse(TypeHandling::isLiteral($type), 'Failed for ' . $type);
    }

    /**
     * data provider for isLiteralReturnsTrueForLiterals
     */
    public static function literals(): \Iterator
    {
        yield ['integer'];
        yield ['int'];
        yield ['float'];
        yield ['double'];
        yield ['boolean'];
        yield ['bool'];
        yield ['string'];
    }

    #[DataProvider('literals')]
    #[Test]
    public function isLiteralReturnsTrueForLiterals(string $type)
    {
        self::assertTrue(TypeHandling::isLiteral($type), 'Failed for ' . $type);
    }

    /**
     * data provider for isSimpleTypeReturnsTrueForSimpleType
     */
    public static function simpleTypes(): \Iterator
    {
        yield ['null', true];
        yield ['integer', true];
        yield ['int', true];
        yield ['float', true];
        yield ['double', true];
        yield ['boolean', true];
        yield ['bool', true];
        yield ['string', true];
        yield ['true', true];
        yield ['false', true];
        yield ['SomeClassThatIsUnknownToPhpAtThisPoint', false];
        yield ['array', true];
        yield ['ArrayObject', false];
        yield ['SplObjectStorage', false];
        yield ['Doctrine\Common\Collections\Collection', false];
        yield ['Doctrine\Common\Collections\ArrayCollection', false];
        yield ['IteratorAggregate', false];
        yield ['Iterator', false];
        yield ['resource', false];
        yield ['parent', false];
        yield ['static', false];
        yield ['self', false];
        yield ['void', false];
        yield ['never', false];
    }

    #[DataProvider('simpleTypes')]
    #[Test]
    public function isSimpleTypeReturnsTrueForSimpleType(string $type, bool $expected)
    {
        self::assertSame($expected, TypeHandling::isSimpleType($type), 'Failed for ' . $type);
    }

    /**
     * data provider for isCollectionTypeReturnsTrueForCollectionType
     */
    public static function collectionTypes(): \Iterator
    {
        yield ['null', false];
        yield ['integer', false];
        yield ['int', false];
        yield ['float', false];
        yield ['double', false];
        yield ['boolean', false];
        yield ['bool', false];
        yield ['string', false];
        yield ['true', false];
        yield ['false', false];
        yield ['SomeClassThatIsUnknownToPhpAtThisPoint', false];
        yield ['array', true];
        yield ['ArrayObject', true];
        yield ['SplObjectStorage', true];
        yield ['Doctrine\Common\Collections\Collection', true];
        yield ['Doctrine\Common\Collections\ArrayCollection', true];
        yield ['IteratorAggregate', true];
        yield ['Iterator', true];
    }

    #[DataProvider('collectionTypes')]
    #[Test]
    public function isCollectionTypeReturnsTrueForCollectionType(string $type, bool $expected)
    {
        self::assertSame($expected, TypeHandling::isCollectionType($type), 'Failed for ' . $type);
    }

    /**
     * data provider for isUnionTypeReturnsTrueForUnionType
     */
    public static function unionAndIntersectionTypes(): \Iterator
    {
        yield ['null', false, false];
        yield ['integer', false, false];
        yield ['int', false, false];
        yield ['float', false, false];
        yield ['double', false, false];
        yield ['boolean', false, false];
        yield ['integer|null', true, false];
        yield ['integer|string', true, false];
        yield ['integer|false', true, false];
        yield ['SomeClassThatIsUnknownToPhpAtThisPoint|false', true, false];
        yield ['SomeClassThatIsUnknownToPhpAtThisPoint', false, false];
        yield ['ArrayObject', false, false];
        yield ['Iterator&Traversable', false, true];
    }

    #[DataProvider('unionAndIntersectionTypes')]
    #[Test]
    public function isUnionTypeReturnsTrueForUnionType(string $type, bool $expectUnionType, bool $expectIntersectionType)
    {
        self::assertSame($expectUnionType, TypeHandling::isUnionType($type), 'Failed for ' . $type);
    }

    #[DataProvider('unionAndIntersectionTypes')]
    #[Test]
    public function isIntersectionTypeReturnsTrueForIntersectionTypes(string $type, bool $expectUnionType, bool $expectIntersectionType)
    {
        self::assertSame($expectIntersectionType, TypeHandling::isIntersectionType($type), 'Failed for ' . $type);
    }

    /**
     * data provider for stripNullableTypesReturnsOnlyTheType
     */
    public static function nullableTypes(): \Iterator
    {
        yield ['integer|null', 'integer'];
        yield ['null|int', 'int'];
        yield ['?int', 'int'];
        yield ['array|null', 'array'];
        yield ['?array', 'array'];
        yield ['ArrayObject|null', 'ArrayObject'];
        yield ['null|SplObjectStorage', 'SplObjectStorage'];
        yield ['Doctrine\Common\Collections\Collection|null', 'Doctrine\Common\Collections\Collection'];
        yield ['Doctrine\Common\Collections\ArrayCollection|null', 'Doctrine\Common\Collections\ArrayCollection'];
        yield ['array<\Some\Other\Class>|null', 'array<\Some\Other\Class>'];
        yield ['ArrayObject<int>|null', 'ArrayObject<int>'];
        yield ['?ArrayObject<int>', 'ArrayObject<int>'];
        yield ['SplObjectStorage<\object>|null', 'SplObjectStorage<\object>'];
        yield ['Doctrine\Common\Collections\Collection<ElementType>|null', 'Doctrine\Common\Collections\Collection<ElementType>'];
        yield ['Doctrine\Common\Collections\ArrayCollection<string>|null', 'Doctrine\Common\Collections\ArrayCollection<string>'];
        // This is not even a use case for Flow and is bad API design, but we still should handle it correctly.
        yield ['integer|null|bool', 'integer|bool'];
        yield ['?int|null', 'int'];
        // Types might also contain underscores at various points.
        yield ['null|Doctrine\Common\Collections\Array_Collection', 'Doctrine\Common\Collections\Array_Collection'];
        // This is madness. This... is... NULL!
        yield ['null', 'null'];
    }

    #[DataProvider('nullableTypes')]
    #[Test]
    public function stripNullableTypesReturnsOnlyTheType($type, $expectedResult)
    {
        self::assertEquals(
            $expectedResult,
            TypeHandling::stripNullableType($type),
            'Failed for ' . $type
        );
    }

    #[DataProvider('nullableTypes')]
    #[Test]
    public function parseTypeReturnsNullableHint($type, $expectedResult)
    {
        try {
            $parsedType = TypeHandling::parseType($type);
            self::assertTrue(
                $parsedType['nullable'],
                'Failed for ' . $type
            );
        } catch (InvalidTypeException $e) {
            self::assertTrue(true);
        }
    }
}

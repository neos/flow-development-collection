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

use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

/**
 * Testcase for the Utility\TypeHandling class
 */
class TypeHandlingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function parseTypeThrowsExceptionOnInvalidType()
    {
        $this->expectException(InvalidTypeException::class);
        TypeHandling::parseType('$something');
    }

    /**
     * @test
     */
    public function parseTypeThrowsExceptionOnInvalidElementTypeHint()
    {
        $this->expectException(InvalidTypeException::class);
        TypeHandling::parseType('string<integer>');
    }

    /**
     * data provider for parseTypeReturnsArrayWithInformation
     */
    public function types()
    {
        return [
            ['int', ['type' => 'integer', 'elementType' => null, 'nullable' => false]],
            ['string', ['type' => 'string', 'elementType' => null, 'nullable' => false]],
            ['DateTime', ['type' => 'DateTime', 'elementType' => null, 'nullable' => false]],
            ['DateTimeImmutable', ['type' => 'DateTimeImmutable', 'elementType' => null, 'nullable' => false]],
            ['Neos\Foo\Bar', ['type' => 'Neos\Foo\Bar', 'elementType' => null, 'nullable' => false]],
            ['\Neos\Foo\Bar', ['type' => 'Neos\Foo\Bar', 'elementType' => null, 'nullable' => false]],
            ['\stdClass', ['type' => 'stdClass', 'elementType' => null, 'nullable' => false]],
            ['array<integer>', ['type' => 'array', 'elementType' => 'integer', 'nullable' => false]],
            ['ArrayObject<string>', ['type' => 'ArrayObject', 'elementType' => 'string', 'nullable' => false]],
            ['SplObjectStorage<Neos\Foo\Bar>', ['type' => 'SplObjectStorage', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]],
            ['SplObjectStorage<\Neos\Foo\Bar>', ['type' => 'SplObjectStorage', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]],
            ['Doctrine\Common\Collections\Collection<\Neos\Foo\Bar>', ['type' => 'Doctrine\Common\Collections\Collection', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]],
            ['Doctrine\Common\Collections\ArrayCollection<\Neos\Foo\Bar>', ['type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'Neos\Foo\Bar', 'nullable' => false]],
            ['\SomeClass with appendix', ['type' => 'SomeClass', 'elementType' => null, 'nullable' => false]],

            // Types might also contain underscores at various points.
            ['Doctrine\Common\Collections\Special_Class_With_Underscores', ['type' => 'Doctrine\Common\Collections\Special_Class_With_Underscores', 'elementType' => null, 'nullable' => false]],
            ['Doctrine\Common\Collections\ArrayCollection<\Neos\Foo_\Bar>', ['type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'Neos\Foo_\Bar', 'nullable' => false]],
        ];
    }

    /**
     * @test
     * @dataProvider types
     */
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
    public function compositeTypes()
    {
        return [
            ['integer', 'integer'],
            ['int', 'int'],
            ['array', 'array'],
            ['ArrayObject', 'ArrayObject'],
            ['SplObjectStorage', 'SplObjectStorage'],
            ['Doctrine\Common\Collections\Collection', 'Doctrine\Common\Collections\Collection'],
            ['Doctrine\Common\Collections\ArrayCollection', 'Doctrine\Common\Collections\ArrayCollection'],
            ['array<\Some\Other\Class>', 'array'],
            ['ArrayObject<int>', 'ArrayObject'],
            ['SplObjectStorage<\object>', 'SplObjectStorage'],
            ['Doctrine\Common\Collections\Collection<ElementType>', 'Doctrine\Common\Collections\Collection'],
            ['Doctrine\Common\Collections\ArrayCollection<>', 'Doctrine\Common\Collections\ArrayCollection'],

            // Types might also contain underscores at various points.
            ['Doctrine\Common\Collections\Array_Collection<>', 'Doctrine\Common\Collections\Array_Collection'],
        ];
    }

    /**
     * @test
     * @dataProvider compositeTypes
     */
    public function extractCollectionTypeReturnsOnlyTheMainType(string $type, string $expectedResult)
    {
        self::assertEquals(
            $expectedResult,
            TypeHandling::truncateElementType($type),
            'Failed for ' . $type
        );
    }

    /**
     * data provider for normalizeTypesReturnsNormalizedType
     */
    public function normalizeTypes()
    {
        return [
            ['int', 'integer'],
            ['double', 'float'],
            ['bool', 'boolean'],
            ['string', 'string']
        ];
    }

    /**
     * @test
     * @dataProvider normalizeTypes
     */
    public function normalizeTypesReturnsNormalizedType(string $type, string $normalized)
    {
        self::assertEquals(TypeHandling::normalizeType($type), $normalized);
    }

    /**
     * data provider for isLiteralReturnsFalseForNonLiteralTypes
     */
    public function nonLiteralTypes()
    {
        return [
            ['DateTime'],
            ['\Foo\Bar'],
            ['array'],
            ['ArrayObject'],
            ['stdClass']
        ];
    }

    /**
     * @test
     * @dataProvider nonliteralTypes
     */
    public function isLiteralReturnsFalseForNonLiteralTypes(string $type)
    {
        self::assertFalse(TypeHandling::isLiteral($type), 'Failed for ' . $type);
    }

    /**
     * data provider for isLiteralReturnsTrueForLiteralType
     */
    public function literalTypes()
    {
        return [
            ['integer'],
            ['int'],
            ['float'],
            ['double'],
            ['boolean'],
            ['bool'],
            ['string']
        ];
    }

    /**
     * @test
     * @dataProvider literalTypes
     */
    public function isLiteralReturnsTrueForLiteralType(string $type)
    {
        self::assertTrue(TypeHandling::isLiteral($type), 'Failed for ' . $type);
    }

    /**
     * data provider for isCollectionTypeReturnsTrueForCollectionType
     */
    public function collectionTypes()
    {
        return [
            ['integer', false],
            ['int', false],
            ['float', false],
            ['double', false],
            ['boolean', false],
            ['bool', false],
            ['string', false],
            ['SomeClassThatIsUnknownToPhpAtThisPoint', false],
            ['array', true],
            ['ArrayObject', true],
            ['SplObjectStorage', true],
            ['Doctrine\Common\Collections\Collection', true],
            ['Doctrine\Common\Collections\ArrayCollection', true],
            ['IteratorAggregate', true],
            ['Iterator', true]
        ];
    }

    /**
     * @test
     * @dataProvider collectionTypes
     */
    public function isCollectionTypeReturnsTrueForCollectionType(string $type, bool $expected)
    {
        self::assertSame($expected, TypeHandling::isCollectionType($type), 'Failed for ' . $type);
    }

    /**
     * data provider for stripNullableTypesReturnsOnlyTheType
     */
    public function nullableTypes()
    {
        return [
            ['integer|null', 'integer'],
            ['null|int', 'int'],
            ['?int', 'int'],
            ['array|null', 'array'],
            ['?array', 'array'],
            ['ArrayObject|null', 'ArrayObject'],
            ['null|SplObjectStorage', 'SplObjectStorage'],
            ['Doctrine\Common\Collections\Collection|null', 'Doctrine\Common\Collections\Collection'],
            ['Doctrine\Common\Collections\ArrayCollection|null', 'Doctrine\Common\Collections\ArrayCollection'],
            ['array<\Some\Other\Class>|null', 'array<\Some\Other\Class>'],
            ['ArrayObject<int>|null', 'ArrayObject<int>'],
            ['?ArrayObject<int>', 'ArrayObject<int>'],
            ['SplObjectStorage<\object>|null', 'SplObjectStorage<\object>'],
            ['Doctrine\Common\Collections\Collection<ElementType>|null', 'Doctrine\Common\Collections\Collection<ElementType>'],
            ['Doctrine\Common\Collections\ArrayCollection<string>|null', 'Doctrine\Common\Collections\ArrayCollection<string>'],

            // This is not even a use case for Flow and is bad API design, but we still should handle it correctly.
            ['integer|null|bool', 'integer|bool'],
            ['?int|null', 'int'],

            // Types might also contain underscores at various points.
            ['null|Doctrine\Common\Collections\Array_Collection', 'Doctrine\Common\Collections\Array_Collection'],

            // This is madness. This... is... NULL!
            ['null', 'null']
        ];
    }

    /**
     * @test
     * @dataProvider nullableTypes
     */
    public function stripNullableTypesReturnsOnlyTheType($type, $expectedResult)
    {
        self::assertEquals(
            $expectedResult,
            TypeHandling::stripNullableType($type),
            'Failed for ' . $type
        );
    }

    /**
     * @test
     * @dataProvider nullableTypes
     */
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

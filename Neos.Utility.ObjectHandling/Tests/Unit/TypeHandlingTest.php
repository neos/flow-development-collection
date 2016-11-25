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

use Neos\Utility\TypeHandling;

/**
 * Testcase for the Utility\TypeHandling class
 */
class TypeHandlingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \Neos\Utility\Exception\InvalidTypeException
     */
    public function parseTypeThrowsExceptionOnInvalidType()
    {
        TypeHandling::parseType('$something');
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\InvalidTypeException
     */
    public function parseTypeThrowsExceptionOnInvalidElementTypeHint()
    {
        TypeHandling::parseType('string<integer>');
    }

    /**
     * data provider for parseTypeReturnsArrayWithInformation
     */
    public function types()
    {
        return [
            ['int', ['type' => 'integer', 'elementType' => null]],
            ['string', ['type' => 'string', 'elementType' => null]],
            ['DateTime', ['type' => 'DateTime', 'elementType' => null]],
            ['TYPO3\Foo\Bar', ['type' => 'TYPO3\Foo\Bar', 'elementType' => null]],
            ['\TYPO3\Foo\Bar', ['type' => 'TYPO3\Foo\Bar', 'elementType' => null]],
            ['\stdClass', ['type' => 'stdClass', 'elementType' => null]],
            ['array<integer>', ['type' => 'array', 'elementType' => 'integer']],
            ['ArrayObject<string>', ['type' => 'ArrayObject', 'elementType' => 'string']],
            ['SplObjectStorage<TYPO3\Foo\Bar>', ['type' => 'SplObjectStorage', 'elementType' => 'TYPO3\Foo\Bar']],
            ['SplObjectStorage<\TYPO3\Foo\Bar>', ['type' => 'SplObjectStorage', 'elementType' => 'TYPO3\Foo\Bar']],
            ['Doctrine\Common\Collections\Collection<\TYPO3\Foo\Bar>', ['type' => 'Doctrine\Common\Collections\Collection', 'elementType' => 'TYPO3\Foo\Bar']],
            ['Doctrine\Common\Collections\ArrayCollection<\TYPO3\Foo\Bar>', ['type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'TYPO3\Foo\Bar']],
            ['\SomeClass with appendix', ['type' => 'SomeClass', 'elementType' => null]],

            // Types might also contain underscores at various points.
            ['Doctrine\Common\Collections\Special_Class_With_Underscores', ['type' => 'Doctrine\Common\Collections\Special_Class_With_Underscores', 'elementType' => null]],
            ['Doctrine\Common\Collections\ArrayCollection<\TYPO3\Foo_\Bar>', ['type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'TYPO3\Foo_\Bar']],
        ];
    }

    /**
     * @test
     * @dataProvider types
     */
    public function parseTypeReturnsArrayWithInformation($type, $expectedResult)
    {
        $this->assertEquals(
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
    public function extractCollectionTypeReturnsOnlyTheMainType($type, $expectedResult)
    {
        $this->assertEquals(
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
    public function normalizeTypesReturnsNormalizedType($type, $normalized)
    {
        $this->assertEquals(TypeHandling::normalizeType($type), $normalized);
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
    public function isLiteralReturnsFalseForNonLiteralTypes($type)
    {
        $this->assertFalse(TypeHandling::isLiteral($type), 'Failed for ' . $type);
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
    public function isLiteralReturnsTrueForLiteralType($type)
    {
        $this->assertTrue(TypeHandling::isLiteral($type), 'Failed for ' . $type);
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
            ['Doctrine\Common\Collections\ArrayCollection', true]
        ];
    }

    /**
     * @test
     * @dataProvider collectionTypes
     */
    public function isCollectionTypeReturnsTrueForCollectionType($type, $expected)
    {
        $this->assertSame($expected, TypeHandling::isCollectionType($type), 'Failed for ' . $type);
    }
}

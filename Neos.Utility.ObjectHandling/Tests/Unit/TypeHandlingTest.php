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

use TYPO3\Flow\Utility\TypeHandling;

/**
 * Testcase for the Utility\TypeHandling class
 *
 */
class TypeHandlingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Utility\Exception\InvalidTypeException
     */
    public function parseTypeThrowsExceptionOnInvalidType()
    {
        TypeHandling::parseType('something not a type');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Utility\Exception\InvalidTypeException
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
        return array(
            array('int', array('type' => 'integer', 'elementType' => null)),
            array('string', array('type' => 'string', 'elementType' => null)),
            array('DateTime', array('type' => 'DateTime', 'elementType' => null)),
            array('TYPO3\Foo\Bar', array('type' => 'TYPO3\Foo\Bar', 'elementType' => null)),
            array('\TYPO3\Foo\Bar', array('type' => 'TYPO3\Foo\Bar', 'elementType' => null)),
            array('array<integer>', array('type' => 'array', 'elementType' => 'integer')),
            array('ArrayObject<string>', array('type' => 'ArrayObject', 'elementType' => 'string')),
            array('SplObjectStorage<TYPO3\Foo\Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'TYPO3\Foo\Bar')),
            array('SplObjectStorage<\TYPO3\Foo\Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'TYPO3\Foo\Bar')),
            array('Doctrine\Common\Collections\Collection<\TYPO3\Foo\Bar>', array('type' => 'Doctrine\Common\Collections\Collection', 'elementType' => 'TYPO3\Foo\Bar')),
            array('Doctrine\Common\Collections\ArrayCollection<\TYPO3\Foo\Bar>', array('type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'TYPO3\Foo\Bar')),

            // Types might also contain underscores at various points.
            array('Doctrine\Common\Collections\Special_Class_With_Underscores', array('type' => 'Doctrine\Common\Collections\Special_Class_With_Underscores', 'elementType' => null)),
            array('Doctrine\Common\Collections\ArrayCollection<\TYPO3\Foo_\Bar>', array('type' => 'Doctrine\Common\Collections\ArrayCollection', 'elementType' => 'TYPO3\Foo_\Bar')),
        );
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
        return array(
            array('integer', 'integer'),
            array('int', 'int'),
            array('array', 'array'),
            array('ArrayObject', 'ArrayObject'),
            array('SplObjectStorage', 'SplObjectStorage'),
            array('Doctrine\Common\Collections\Collection', 'Doctrine\Common\Collections\Collection'),
            array('Doctrine\Common\Collections\ArrayCollection', 'Doctrine\Common\Collections\ArrayCollection'),
            array('array<\Some\Other\Class>', 'array'),
            array('ArrayObject<int>', 'ArrayObject'),
            array('SplObjectStorage<\object>', 'SplObjectStorage'),
            array('Doctrine\Common\Collections\Collection<ElementType>', 'Doctrine\Common\Collections\Collection'),
            array('Doctrine\Common\Collections\ArrayCollection<>', 'Doctrine\Common\Collections\ArrayCollection'),

            // Types might also contain underscores at various points.
            array('Doctrine\Common\Collections\Array_Collection<>', 'Doctrine\Common\Collections\Array_Collection'),
        );
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
        return array(
            array('int', 'integer'),
            array('double', 'float'),
            array('bool', 'boolean'),
            array('string', 'string')
        );
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
        return array(
            array('DateTime'),
            array('\Foo\Bar'),
            array('array'),
            array('ArrayObject'),
            array('stdClass')
        );
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
        return array(
            array('integer'),
            array('int'),
            array('float'),
            array('double'),
            array('boolean'),
            array('bool'),
            array('string')
        );
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
        return array(
            array('integer', false),
            array('int', false),
            array('float', false),
            array('double', false),
            array('boolean', false),
            array('bool', false),
            array('string', false),
            array('SomeClassThatIsUnknownToPhpAtThisPoint', false),
            array('array', true),
            array('ArrayObject', true),
            array('SplObjectStorage', true),
            array('Doctrine\Common\Collections\Collection', true),
            array('Doctrine\Common\Collections\ArrayCollection', true)
        );
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

<?php
namespace Neos\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Property\TypeConverter\TypedArrayConverter;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the TypedArrayConverter
 *
 */
class TypedArrayConverterTest extends UnitTestCase
{
    /**
     * @var TypedArrayConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new TypedArrayConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(2, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider()
    {
        return [
            ['targetType' => 'SomeTargetType', 'expectedResult' => false],
            ['targetType' => 'array', 'expectedResult' => false],

            ['targetType' => 'array<string>', 'expectedResult' => true],
            ['targetType' => 'array<Some\Element\Type>', 'expectedResult' => true],
            ['targetType' => '\array<\int>', 'expectedResult' => true],
        ];
    }

    /**
     * @test
     * @dataProvider canConvertFromDataProvider
     */
    public function canConvertFromTests($targetType, $expectedResult)
    {
        $actualResult = $this->converter->canConvertFrom([], $targetType);
        if ($expectedResult === true) {
            $this->assertTrue($actualResult);
        } else {
            $this->assertFalse($actualResult);
        }
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        $this->assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted(''));
    }
}

<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Property\TypeConverter\TypedArrayConverter;
use TYPO3\Flow\Tests\UnitTestCase;

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
        $this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(2, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider()
    {
        return array(
            array('targetType' => 'SomeTargetType', 'expectedResult' => false),
            array('targetType' => 'array', 'expectedResult' => false),

            array('targetType' => 'array<string>', 'expectedResult' => true),
            array('targetType' => 'array<Some\Element\Type>', 'expectedResult' => true),
            array('targetType' => '\array<\int>', 'expectedResult' => true),
        );
    }

    /**
     * @test
     * @dataProvider canConvertFromDataProvider
     */
    public function canConvertFromTests($targetType, $expectedResult)
    {
        $actualResult = $this->converter->canConvertFrom(array(), $targetType);
        if ($expectedResult === true) {
            $this->assertTrue($actualResult);
        } else {
            $this->assertFalse($actualResult);
        }
    }
}

<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Property\TypeConverter\ArrayFromObjectConverter;

/**
 * Testcase for the ArrayFromObject converter
 *
 */
class ArrayFromObjectConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var ArrayFromObjectConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new ArrayFromObjectConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('object'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedReturnsSubObjectsArray()
    {
        $source = new \stdClass();
        $source->first = 'Foo';
        $source->second = new \stdClass();
        $this->assertEquals(array('second' => new \stdClass()), $this->converter->getSourceChildPropertiesToBeConverted($source));
    }

    public function objectToArrayDataProvider()
    {
        return array(
            array(array('foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz'), array('foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz', '__type' => 'stdClass' )),
            array(array('foo' => 'Foo', 'bar' => array( 'bar' => 'Bar', 'baz' => 'Baz' )), array('foo' => 'Foo', 'bar' => array( 'bar' => 'Bar', 'baz' => 'Baz', '__type' => 'stdClass' ), '__type' => 'stdClass' )),
            array(new \stdClass(), array( '__type' => 'stdClass' ))
        );
    }

    /**
     * @test
     * @dataProvider objectToArrayDataProvider
     */
    public function canConvertFromObjectToArray($source, $expectedResult)
    {
        if (is_array($source)) {
            $source = json_decode(json_encode($source), false);
        }

        $convertedChildProperties = array_map(function($value) {
            return $this->converter->convertFrom($value, 'array', array(), null);
        }, $this->converter->getSourceChildPropertiesToBeConverted($source));
        $this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', $convertedChildProperties, null));
    }
}

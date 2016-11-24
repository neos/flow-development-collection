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

use Neos\Flow\Property\TypeConverter\ArrayFromObjectConverter;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the ArrayFromObject converter
 *
 */
class ArrayFromObjectConverterTest extends UnitTestCase
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
        $this->assertEquals(['object'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
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
        $this->assertEquals(['second' => new \stdClass()], $this->converter->getSourceChildPropertiesToBeConverted($source));
    }

    public function objectToArrayDataProvider()
    {
        return [
            [['foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz'], ['foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz', '__type' => 'stdClass']],
            [['foo' => 'Foo', 'bar' => ['bar' => 'Bar', 'baz' => 'Baz']], ['foo' => 'Foo', 'bar' => ['bar' => 'Bar', 'baz' => 'Baz', '__type' => 'stdClass'], '__type' => 'stdClass']],
            [new \stdClass(), ['__type' => 'stdClass']]
        ];
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

        $convertedChildProperties = array_map(function ($value) {
            return $this->converter->convertFrom($value, 'array', [], null);
        }, $this->converter->getSourceChildPropertiesToBeConverted($source));
        $this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', $convertedChildProperties, null));
    }
}

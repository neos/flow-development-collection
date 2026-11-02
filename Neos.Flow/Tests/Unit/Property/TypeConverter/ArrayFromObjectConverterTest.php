<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the ArrayFromObject converter
 *
 */
final class ArrayFromObjectConverterTest extends UnitTestCase
{
    /**
     * @var ArrayFromObjectConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new ArrayFromObjectConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['object'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedReturnsSubObjectsArray()
    {
        $source = new \stdClass();
        $source->first = 'Foo';
        $source->second = new \stdClass();
        self::assertEquals(['second' => new \stdClass()], $this->converter->getSourceChildPropertiesToBeConverted($source));
    }

    public static function objectToArrayDataProvider(): \Iterator
    {
        yield [['foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz'], ['foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz', '__type' => 'stdClass']];
        yield [['foo' => 'Foo', 'bar' => ['bar' => 'Bar', 'baz' => 'Baz']], ['foo' => 'Foo', 'bar' => ['bar' => 'Bar', 'baz' => 'Baz', '__type' => 'stdClass'], '__type' => 'stdClass']];
        yield [new \stdClass(), ['__type' => 'stdClass']];
    }

    #[DataProvider('objectToArrayDataProvider')]
    #[Test]
    public function canConvertFromObjectToArray($source, $expectedResult)
    {
        if (is_array($source)) {
            $source = json_decode(json_encode($source), false);
        }

        $convertedChildProperties = array_map(function ($value) {
            return $this->converter->convertFrom($value, 'array', [], null);
        }, $this->converter->getSourceChildPropertiesToBeConverted($source));
        self::assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', $convertedChildProperties, null));
    }
}

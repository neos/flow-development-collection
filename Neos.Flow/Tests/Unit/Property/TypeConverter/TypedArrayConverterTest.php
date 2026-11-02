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
use Neos\Flow\Property\TypeConverter\TypedArrayConverter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the TypedArrayConverter
 *
 */
final class TypedArrayConverterTest extends UnitTestCase
{
    /**
     * @var TypedArrayConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new TypedArrayConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(2, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function canConvertFromDataProvider(): \Iterator
    {
        yield ['targetType' => 'SomeTargetType', 'expectedResult' => false];
        yield ['targetType' => 'array', 'expectedResult' => false];
        yield ['targetType' => 'array<string>', 'expectedResult' => true];
        yield ['targetType' => 'array<Some\Element\Type>', 'expectedResult' => true];
        yield ['targetType' => '\array<\int>', 'expectedResult' => true];
    }

    #[DataProvider('canConvertFromDataProvider')]
    #[Test]
    public function canConvertFromTests($targetType, $expectedResult)
    {
        $actualResult = $this->converter->canConvertFrom([], $targetType);
        if ($expectedResult === true) {
            self::assertTrue($actualResult);
        } else {
            self::assertFalse($actualResult);
        }
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted(''));
    }
}

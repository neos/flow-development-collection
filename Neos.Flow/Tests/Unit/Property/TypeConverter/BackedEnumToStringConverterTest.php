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

require_once(__DIR__ . '/Fixture/ExampleIntBackedEnum.php');
require_once(__DIR__ . '/Fixture/ExampleStringBackedEnum.php');

use Neos\Flow\Property\TypeConverter\BackedEnumToStringConverter;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\ExampleIntBackedEnum;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\ExampleStringBackedEnum;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the BackedEnumToStringConverter
 *
 * @covers \Neos\Flow\Property\TypeConverter\BackedEnumToStringConverter<extended>
 */
class BackedEnumToStringConverterTest extends TestCase
{
    /**
     * @dataProvider canConvertFromProvider
     */
    public function testCanConvertFrom(ExampleIntBackedEnum|ExampleStringBackedEnum $source, string $targetType, bool $expectedResult): void
    {
        Assert::assertSame(
            $expectedResult,
            (new BackedEnumToStringConverter())->canConvertFrom($source, $targetType),
        );
    }

    /**
     * @return iterable<string,array{source: string|int, targetType: string, expectedResult: string}>
     */
    public static function canConvertFromProvider(): iterable
    {
        yield 'IntBackedEnum -> string' => [
            'source' => ExampleIntBackedEnum::DEFAULT,
            'targetType' => 'string',
            'expectedResult' => false,
        ];

        yield 'StringBackedEnum -> string' => [
            'source' => ExampleStringBackedEnum::DEFAULT,
            'targetType' => 'string',
            'expectedResult' => true,
        ];
    }
    /**
     * @dataProvider convertFromProvider
     */
    public function testConvertFrom(ExampleIntBackedEnum|ExampleStringBackedEnum $source, string $targetType, string|int $expectedResult): void
    {
        Assert::assertSame(
            $expectedResult,
            (new BackedEnumToStringConverter())->convertFrom($source, $targetType),
        );
    }

    /**
     * @return iterable<string,array{source: string|int, targetType: string, expectedResult: string}>
     */
    public static function convertFromProvider(): iterable
    {
        yield 'StringBackedEnum -> string' => [
            'source' => ExampleStringBackedEnum::DEFAULT,
            'targetType' => 'string',
            'expectedResult' => 'default',
        ];
    }
}

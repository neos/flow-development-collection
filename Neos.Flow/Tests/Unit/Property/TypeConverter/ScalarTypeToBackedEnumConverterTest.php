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

use Neos\Flow\Property\TypeConverter\ScalarTypeToBackedEnumConverter;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\ExampleIntBackedEnum;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\ExampleStringBackedEnum;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the ScalarTypeToBackedEnumConverter
 */
#[CoversClass(ScalarTypeToBackedEnumConverter::class)]
class ScalarTypeToBackedEnumConverterTest extends TestCase
{
    #[DataProvider('canConvertFromProvider')]
    public function testCanConvertFrom(string|int $source, string $targetType, bool $expectedResult): void
    {
        Assert::assertSame(
            $expectedResult,
            (new ScalarTypeToBackedEnumConverter())->canConvertFrom($source, $targetType),
        );
    }

    /**
     * @return iterable<string,array{source: string|int, targetType: string, expectedResult: string}>
     */
    public static function canConvertFromProvider(): iterable
    {
        yield 'string -> IntBackedEnum' => [
            'source' => 'default',
            'targetType' => ExampleIntBackedEnum::class,
            'expectedResult' => false,
        ];

        yield 'non-case int -> IntBackedEnum' => [
            'source' => 43,
            'targetType' => ExampleIntBackedEnum::class,
            'expectedResult' => false,
        ];

        yield 'case int -> IntBackedEnum' => [
            'source' => 42,
            'targetType' => ExampleIntBackedEnum::class,
            'expectedResult' => true,
        ];

        yield 'case int -> StringBackedEnum' => [
            'source' => 42,
            'targetType' => ExampleStringBackedEnum::class,
            'expectedResult' => false,
        ];

        yield 'non-case string -> StringBackedEnum' => [
            'source' => 'other',
            'targetType' => ExampleStringBackedEnum::class,
            'expectedResult' => false,
        ];

        yield 'case string -> StringBackedEnum' => [
            'source' => 'default',
            'targetType' => ExampleStringBackedEnum::class,
            'expectedResult' => true,
        ];
    }
    #[DataProvider('convertFromProvider')]
    public function testConvertFrom(string|int $source, string $targetType, ExampleIntBackedEnum|ExampleStringBackedEnum $expectedResult): void
    {
        Assert::assertSame(
            $expectedResult,
            (new ScalarTypeToBackedEnumConverter())->convertFrom($source, $targetType),
        );
    }

    /**
     * @return iterable<string,array{source: string|int, targetType: string, expectedResult: string}>
     */
    public static function convertFromProvider(): iterable
    {
        yield 'case int -> IntBackedEnum' => [
            'source' => 42,
            'targetType' => ExampleIntBackedEnum::class,
            'expectedResult' => ExampleIntBackedEnum::DEFAULT,
        ];

        yield 'case string -> StringBackedEnum' => [
            'source' => 'default',
            'targetType' => ExampleStringBackedEnum::class,
            'expectedResult' => ExampleStringBackedEnum::DEFAULT,
        ];
    }
}

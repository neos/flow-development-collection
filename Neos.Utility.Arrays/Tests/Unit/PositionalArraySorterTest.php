<?php

namespace Neos\Utility\Arrays\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Exception\InvalidPositionException;
use Neos\Utility\PositionalArraySorter;

/**
 * Tests for the PositionalArraySorter utility class
 */
class PositionalArraySorterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function toArraySortsNumericKeysIfNoPositionMetaDataIsSet(): void
    {
        $array = [2 => 'foo', 1 => 'bar', 'z' => 'baz', 'a' => 'quux'];
        $expectedResult = ['z' => 'baz', 'a' => 'quux', 1 => 'bar', 2 => 'foo'];

        $positionalArraySorter = new PositionalArraySorter($array);
        $sortedArray = $positionalArraySorter->toArray();
        self::assertSame($expectedResult, $sortedArray);
    }

    /**
     * @return array
     */
    public function invalidPositions(): iterable
    {
        return [
            ['subject' => ['foo' => ['position' => 'invalid'], 'first' => []], 'expectedExceptionMessage' => 'The positional string "invalid" (defined for key "foo") is not supported.'],
            ['subject' => ['foo' => ['position' => 'start123'], 'first' => []], 'expectedExceptionMessage' => 'The positional string "start123" (defined for key "foo") is not supported.'],
            ['subject' => ['foo' => ['position' => 'start 12 34'], 'first' => []], 'expectedExceptionMessage' => 'The positional string "start 12 34" (defined for key "foo") is not supported.'],
            ['subject' => ['foo' => ['position' => 'after 12 34 56'], 'first' => []], 'expectedExceptionMessage' => 'The positional string "after 12 34 56" (defined for key "foo") is not supported.'],
            ['subject' => ['third' => ['position' => 'before nonexisting'], 'first' => []], 'expectedExceptionMessage' => 'The positional string "before nonexisting" (defined for key "third") references a non-existing key.'],
            ['subject' => ['third' => ['position' => 'after nonexisting'], 'first' => []], 'expectedExceptionMessage' => 'The positional string "after nonexisting" (defined for key "third") references a non-existing key.'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidPositions
     */
    public function toArrayThrowsExceptionForInvalidPositions(array $subject, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidPositionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $positionalArraySorter = new PositionalArraySorter($subject);
        $positionalArraySorter->toArray();
    }

    public function sampleArrays(): iterable
    {
        yield 'Position end should put element to end' => [
            'subject' => ['second' => ['__meta' => ['position' => 'end']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield 'Position start should put element to start' => [
            'subject' => ['second' => [], 'first' => ['__meta' => ['position' => 'start']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield 'Position start should respect priority' => [
            'subject' => ['second' => ['__meta' => ['position' => 'start 50']], 'first' => ['__meta' => ['position' => 'start 52']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield 'Position end should respect priority' => [
            'subject' => ['second' => ['__meta' => ['position' => 'end 17']], 'first' => ['__meta' => ['position' => 'end']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield 'Positional numbers are in the middle' => [
            'subject' => ['last' => ['__meta' => ['position' => 'end']], 'second' => ['__meta' => ['position' => '17']], 'first' => ['__meta' => ['position' => '5']], 'third' => ['__meta' => ['position' => '18']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second', 'third', 'last']
        ];
        yield 'Position before adds before named element if present' => [
            'subject' => ['second' => [], 'first' => ['__meta' => ['position' => 'before second']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield 'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.' => [
            'subject' => ['third' => [], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before third 12']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['second', 'first', 'third']
        ];
        yield 'Position before works recursively' => [
            'subject' => ['third' => [], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before second']]],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second', 'third']
        ];
        yield 'Position after adds after named element if present' => [
            'subject' => ['second' => ['__meta' => ['position' => 'after first']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield 'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.' => [
            'subject' => ['third' => ['__meta' => ['position' => 'after first']], 'second' => ['__meta' => ['position' => 'after first 12']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second', 'third']
        ];
        yield 'Position after works recursively' => [
            'subject' => ['third' => ['__meta' => ['position' => 'after second']], 'second' => ['__meta' => ['position' => 'after first']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['first', 'second', 'third']
        ];
        yield 'Array keys may contain special characters' => [
            'subject' => ['thi:rd' => ['position' => 'end'], 'sec.ond' => ['position' => 'before thi:rd'], 'fir-st' => ['position' => 'before sec.ond']],
            'positionPropertyPath' => 'position',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['fir-st', 'sec.ond', 'thi:rd']
        ];
        yield 'Null values are skipped by default' => [
            'subject' => ['foo' => ['p' => 'end'], 'bar' => ['p' => 'start'], 'baz' => null],
            'positionPropertyPath' => 'p',
            'removeNullValues' => true,
            'expectedArrayKeys' => ['bar', 'foo']
        ];
        yield 'Null values are kept if removeNullValues is set to false' => [
            'subject' => ['foo' => ['p' => 'end'], 'bar' => ['p' => 'start'], 'baz' => null],
            'positionPropertyPath' => 'p',
            'removeNullValues' => false,
            'expectedArrayKeys' => ['bar', 'baz', 'foo']
        ];
    }

    /**
     * @test
     * @dataProvider sampleArrays
     */
    public function toArrayTests(array $subject, $positionPropertyPath, ?bool $removeNullValues, array $expectedKeyOrder): void
    {
        $positionalArraySorter = new PositionalArraySorter($subject, $positionPropertyPath, $removeNullValues);
        $result = $positionalArraySorter->toArray();

        self::assertSame($expectedKeyOrder, array_keys($result));
    }

    /**
     * @test
     * @dataProvider sampleArrays
     */
    public function getSortedKeysTests(array $subject, $positionPropertyPath, ?bool $removeNullValues, array $expectedKeyOrder): void
    {
        $positionalArraySorter = new PositionalArraySorter($subject, $positionPropertyPath, $removeNullValues);
        $result = $positionalArraySorter->getSortedKeys();

        self::assertSame($expectedKeyOrder, $result);
    }
}

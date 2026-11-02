<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Eel\Helper\JsonHelper;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for JsonHelper
 */
final class JsonHelperTest extends UnitTestCase
{
    public static function stringifyExamples(): \Iterator
    {
        yield 'string value' => [
            'Foo', '"Foo"'
        ];
        yield 'null value' => [
            null, 'null'
        ];
        yield 'numeric value' => [
            42, '42'
        ];
        yield 'array value' => [
            ['Foo', 'Bar'], '["Foo","Bar"]'
        ];
    }

    #[DataProvider('stringifyExamples')]
    #[Test]
    public function stringifyWorks($value, $expected)
    {
        $helper = new JsonHelper();
        $result = $helper->stringify($value);
        self::assertEquals($expected, $result);
    }

    public static function parseExamples(): \Iterator
    {
        yield 'string value' => [
            ['"Foo"'], 'Foo'
        ];
        yield 'null value' => [
            ['null'], null
        ];
        yield 'numeric value' => [
            ['42'], 42
        ];
        yield 'array value' => [
            ['["Foo","Bar"]'], ['Foo', 'Bar']
        ];
        yield 'object value is parsed as associative array by default' => [
            ['{"name":"Foo"}'], ['name' => 'Foo']
        ];
        yield 'object value without associative array' => [
            ['{"name":"Foo"}', false], (object)['name' => 'Foo']
        ];
    }

    #[DataProvider('parseExamples')]
    #[Test]
    public function parseWorks($arguments, $expected)
    {
        $helper = new JsonHelper();
        $result = $helper->parse(...$arguments);
        self::assertEquals($expected, $result);
    }
}

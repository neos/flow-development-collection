<?php

namespace Neos\Utility\Arrays\Tests\Unit;

use Neos\Utility\ArrayIndexParser;

class ArrayIndexParserTest extends \PHPUnit\Framework\TestCase
{
    public function validPathIndexes(): \Generator
    {
        yield "simple dot index" => [
            "input" => "foo.bar.bung",
            "parsed" => ["foo", "bar", "bung"]
        ];

        yield "plain string" => [
            "input" => "foobar",
            "parsed" => ["foobar"]
        ];

        yield "wrapped with bracket" => [
            "input" => "[foobar]",
            "parsed" => ["foobar"]
        ];

        yield "dots inside bracket" => [
            "input" => "[foo.bar]",
            "parsed" => ["foo.bar"]
        ];

        yield "bracket combined with dot index" => [
            "input" => "[foo.bar].buz",
            "parsed" => ["foo.bar", "buz"]
        ];

        yield "bracket combined with dot index 2" => [
            "input" => "bing[foo.bar].buz",
            "parsed" => ["bing", "foo.bar", "buz"]
        ];

        yield "two bracket combined" => [
            "input" => "bing[foo][buz]",
            "parsed" => ["bing", "foo", "buz"]
        ];
    }

    /** @dataProvider validPathIndexes */
    public function testPathIndexes(string $input, array $parsed): void
    {
        self::assertEquals($parsed, ArrayIndexParser::parseFromString($input));
    }

    public function invalidPathIndexes(): \Generator
    {
        yield "leading dot" => [
            "string" => ".foo",
            "code" => 1677952251960
        ];

        yield "leading dot 2" => [
            "string" => "foo.",
            "code" => 1677952251960
        ];

        yield "double dot" => [
            "string" => "foo..bar",
            "code" => 1677952251960
        ];

        yield "empty bracket" => [
            "string" => "foo[].foo",
            "code" => 1677952251960
        ];

        yield "unclosed bracket" => [
            "string" => "foo[bar",
            "code" => 1677945736908
        ];

        yield "bracket out of context" => [
            "string" => "foo]bar",
            "code" => 1677944502145
        ];

        yield "nested bracket" => [
            "string" => "bung[f[o]o].bar",
            "code" => 1677944492915
        ];

        yield "bracket without following dot" => [
            "string" => "bung[foo]bar",
            "code" => 1677953277708
        ];

        yield "bracket with previous dot" => [
            "string" => "bung.[foo]",
            "code" => 1677952251960
        ];
    }

    /** @dataProvider invalidPathIndexes */
    public function testInvalidPathIndexes(string $input, int $code): void
    {
        $this->expectExceptionCode($code);
        ArrayIndexParser::parseFromString($input);
    }
}

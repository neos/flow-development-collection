<?php
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

use Neos\Eel\Helper\StringHelper;
use Neos\Eel\Tests\Unit\Fixtures\TestObject;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for StringHelper
 */
class StringHelperTest extends UnitTestCase
{
    public function substrExamples()
    {
        return [
            'positive start and length lower count' => ['Hello, World!', 7, 5, 'World'],
            'start equal to count' => ['Foo', 3, 42, ''],
            'start greater than count' => ['Foo', 42, 5, ''],
            'start negative' => ['Hello, World!', -6, 5, 'World'],
            'start negative larger than abs(count)' => ['Hello, World!', -42, 5, 'Hello'],
            'start positive and length omitted' => ['Hello, World!', 7, null, 'World!'],
            'start positive and length is 0' => ['Hello, World!', 7, 0, ''],
            'start positive and length is negative' => ['Hello, World!', 7, -1, ''],
            'unicode content is extracted' => ['Öaßaä', 2, 1, 'ß']
        ];
    }

    /**
     * @test
     * @dataProvider substrExamples
     */
    public function substrWorks($string, $start, $length, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->substr($string, $start, $length);
        self::assertSame($expected, $result);
    }

    public function substringExamples()
    {
        return [
            'start equals end' => ['Hello, World!', 7, 7, ''],
            'end omitted' => ['Hello, World!', 7, null, 'World!'],
            'negative start' => ['Hello, World!', -7, null, 'Hello, World!'],
            'negative end' => ['Hello, World!', 5, -5, 'Hello'],
            'start greater than end' => ['Hello, World!', 5, 0, 'Hello'],
            'start greater than count' => ['Hello, World!', 15, 0, 'Hello, World!'],
            'end greater than count' => ['Hello, World!', 7, 15, 'World!'],
            'unicode content is extracted' => ['Öaßaä', 2, 3, 'ß']
        ];
    }

    /**
     * @test
     * @dataProvider substringExamples
     */
    public function substringWorks($string, $start, $end, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->substring($string, $start, $end);
        self::assertSame($expected, $result);
    }

    public function charAtExamples()
    {
        return [
            'index in string' => ['Hello, World!', 5, ','],
            'index greater than count' => ['Hello, World!', 42, ''],
            'index negative' => ['Hello, World!', -1, ''],
            'unicode content can be accessed' => ['Öaßaü', 2, 'ß']
        ];
    }

    /**
     * @test
     * @dataProvider charAtExamples
     */
    public function charAtWorks($string, $index, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->charAt($string, $index);
        self::assertSame($expected, $result);
    }

    public function endsWithExamples()
    {
        return [
            'search matched' => ['To be, or not to be, that is the question.', 'question.', null, true],
            'search not matched' => ['To be, or not to be, that is the question.', 'to be', null, false],
            'search with position' => ['To be, or not to be, that is the question.', 'to be', 19, true],
            'unicode content can be searched' => ['Öaßaü', 'aü', null, true]
        ];
    }

    /**
     * @test
     * @dataProvider endsWithExamples
     */
    public function endsWithWorks($string, $search, $position, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->endsWith($string, $search, $position);
        self::assertSame($expected, $result);
    }

    public function chrExamples()
    {
        return [
            ['value' => 65, 'expected' => 'A'],
            ['value' => 256, 'expected' => chr(256)],
            ['value' => 0, 'expected' => chr(0)],
        ];
    }

    /**
     * @test
     * @dataProvider chrExamples
     */
    public function chrWorks($value, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->chr($value);
        self::assertSame($expected, $result);
    }

    public function ordExamples()
    {
        return [
            ['value' => 'A', 'expected' => 65],
            ['value' => '', 'expected' => 0],
            ['value' => 1, 'expected' => 49],
            ['value' => 'longer string', 'expected' => 108],
        ];
    }

    /**
     * @test
     * @dataProvider ordExamples
     */
    public function ordWorks($value, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->ord($value);
        self::assertSame($expected, $result);
    }

    public function indexOfExamples()
    {
        return [
            'match at start' => ['Blue Whale', 'Blue', null, 0],
            'no match' => ['Blute', 'Blue', null, -1],
            'from index at start' => ['Blue Whale', 'Whale', 0, 5],
            'from index at begin of match' => ['Blue Whale', 'Whale', 5, 5],
            'from index after match' => ['Blue Whale', 'Whale', 6, -1],
            'empty search' => ['Blue Whale', '', null, 0],
            'empty search with from index' => ['Blue Whale', '', 9, 9],
            'empty search with from index larger than count' => ['Blue Whale', '', 11, 10],
            'case sensitive match' => ['Blue Whale', 'blue', null, -1],
            'unicode content is matched' => ['Öaßaü', 'ßa', null, 2]
        ];
    }

    /**
     * @test
     * @dataProvider indexOfExamples
     */
    public function indexOfWorks($string, $search, $fromIndex, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->indexOf($string, $search, $fromIndex);
        self::assertSame($expected, $result);
    }

    public function lastIndexOfExamples()
    {
        return [
            'match last occurence' => ['canal', 'a', null, 3],
            'match with from index' => ['canal', 'a', 2, 1],
            'no match with from index too low' => ['canal', 'a', 0, -1],
            'no match' => ['canal', 'x', null, -1],
            'unicode content is matched' => ['Öaßaü', 'a', null, 3]
        ];
    }

    /**
     * @test
     * @dataProvider lastIndexOfExamples
     */
    public function lastIndexOfWorks($string, $search, $fromIndex, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->lastIndexOf($string, $search, $fromIndex);
        self::assertSame($expected, $result);
    }

    public function pregMatchExamples()
    {
        return [
            'matches' => ['For more information, see Chapter 3.4.5.1', '/(chapter \d+(\.\d)*)/i', ['Chapter 3.4.5.1', 'Chapter 3.4.5.1', '.1']]
        ];
    }

    /**
     * @test
     * @dataProvider pregMatchExamples
     */
    public function pregMatchWorks($string, $pattern, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregMatch($string, $pattern);
        self::assertSame($expected, $result);
    }

    public function pregMatchAllExamples()
    {
        return [
            'matches' => ['<hr id="icon-one" /><hr id="icon-two" />', '/id="icon-(.+?)"/', [['id="icon-one"', 'id="icon-two"'],['one','two']]]
        ];
    }

    /**
     * @test
     * @dataProvider pregMatchAllExamples
     */
    public function pregMatchAllWorks($string, $pattern, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregMatchAll($string, $pattern);
        self::assertSame($expected, $result);
    }

    public function pregReplaceExamples()
    {
        return [
            'replace non-alphanumeric characters' => ['Some.String with sp:cial characters', '/[[:^alnum:]]/', '-', null, 'Some-String-with-sp-cial-characters'],
            'replace non-alphanumeric characters width limit' => ['Some.String with sp:cial characters', '/[[:^alnum:]]/', '-', 1, 'Some-String with sp:cial characters'],
            'no match' => ['canal', '/x/', 'y', null, 'canal'],
            'unicode replacement' => ['Öaßaü', '/aßa/', 'g', null, 'Ögü'],
            'references' => ['2016-08-31', '/([0-9]+)-([0-9]+)-([0-9]+)/', '$3.$2.$1', null, '31.08.2016']
        ];
    }

    /**
     * @test
     * @dataProvider pregReplaceExamples
     */
    public function pregReplaceWorks($string, $pattern, $replace, $limit, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregReplace($string, $pattern, $replace, $limit);
        self::assertSame($expected, $result);
    }

    public function pregSplitExamples()
    {
        return [
            'matches' => ['foo bar   baz', '/\s+/', null, ['foo', 'bar', 'baz']],
            'matches with limit' => ['first second third', '/\s+/', 2, ['first', 'second third']]
        ];
    }

    /**
     * @test
     * @dataProvider pregSplitExamples
     */
    public function pregMSplitWorks($string, $pattern, $limit, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregSplit($string, $pattern, $limit);
        self::assertSame($expected, $result);
    }

    public function replaceExamples()
    {
        return [
            'replace' => ['canal', 'ana', 'oo', 'cool'],
            'no match' => ['canal', 'x', 'y', 'canal'],
            'unicode replacement' => ['Öaßaü', 'aßa', 'g', 'Ögü']
        ];
    }

    /**
     * @test
     * @dataProvider replaceExamples
     */
    public function replaceWorks($string, $search, $replace, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->replace($string, $search, $replace);
        self::assertSame($expected, $result);
    }


    public function splitExamples()
    {
        return [
            'split' => ['My hovercraft is full of eels', ' ', null, ['My', 'hovercraft', 'is', 'full', 'of', 'eels']],
            'NULL separator' => ['The bad parts', null, null, ['The bad parts']],
            'empty separator' => ['Foo', '', null, ['F', 'o', 'o']],
            'empty separator with limit' => ['Foo', '', 2, ['F', 'o']]
        ];
    }

    /**
     * @test
     * @dataProvider splitExamples
     */
    public function splitWorks($string, $separator, $limit, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->split($string, $separator, $limit);
        self::assertSame($expected, $result);
    }

    public function startsWithExamples()
    {
        return [
            'search matched' => ['To be, or not to be, that is the question.', 'To be', null, true],
            'search not matched' => ['To be, or not to be, that is the question.', 'not to be', null, false],
            'search with position' => ['To be, or not to be, that is the question.', 'that is', 21, true],
            'search with duplicate match' => ['to be, or not to be, that is the question.', 'to be', null, true],
            'unicode content can be searched' => ['Öaßaü', 'Öa', null, true]
        ];
    }

    /**
     * @test
     * @dataProvider startsWithExamples
     */
    public function startsWithWorks($string, $search, $position, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->startsWith($string, $search, $position);
        self::assertSame($expected, $result);
    }

    public function firstLetterToUpperCaseExamples()
    {
        return [
            'lowercase' => ['foo', 'Foo'],
            'firstLetterUpperCase' => ['Foo', 'Foo']
        ];
    }

    /**
     * @test
     * @dataProvider firstLetterToUpperCaseExamples
     */
    public function firstLetterToUpperCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->firstLetterToUpperCase($string);
        self::assertSame($expected, $result);
    }

    public function firstLetterToLowerCaseExamples()
    {
        return [
            'lowercase' => ['foo', 'foo'],
            'firstLetterUpperCase' => ['Foo', 'foo']
        ];
    }

    /**
     * @test
     * @dataProvider firstLetterToLowerCaseExamples
     */
    public function firstLetterToLowerCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->firstLetterToLowerCase($string);
        self::assertSame($expected, $result);
    }

    public function toLowerCaseExamples()
    {
        return [
            'lowercase' => ['Foo bAr BaZ', 'foo bar baz']
        ];
    }

    /**
     * @test
     * @dataProvider toLowerCaseExamples
     */
    public function toLowerCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->toLowerCase($string);
        self::assertSame($expected, $result);
    }

    public function toUpperCaseExamples()
    {
        return [
            'uppercase' => ['Foo bAr BaZ', 'FOO BAR BAZ']
        ];
    }

    /**
     * @test
     * @dataProvider toUpperCaseExamples
     */
    public function toUpperCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->toUpperCase($string);
        self::assertSame($expected, $result);
    }

    public function isBlankExamples()
    {
        return [
            'string with whitespace' => ['  	', true],
            'string with characters' => [' abc ', false],
            'empty string' => ['', true],
            'NULL string' => [null, true]
        ];
    }

    /**
     * @test
     * @dataProvider isBlankExamples
     */
    public function isBlankWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->isBlank($string);
        self::assertSame($expected, $result);
    }

    public function trimExamples()
    {
        return [
            'string with whitespace' => ['  	', null, ''],
            'string with characters and whitespace' => [" Foo Bar \n", null, 'Foo Bar'],
            'empty string' => ['', null, ''],
            'trim with charlist' => ['< abc >', '<>', ' abc ']
        ];
    }

    /**
     * @test
     * @dataProvider trimExamples
     */
    public function trimWorks($string, $charlist, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->trim($string, $charlist);
        self::assertSame($expected, $result);
    }

    public function typeConversionExamples()
    {
        return [
            'string numeric value' => ['toString', 42, '42'],
            'string true boolean value' => ['toString', true, '1'],
            'string false boolean value' => ['toString', false, ''],

            'integer numeric value' => ['toInteger', '42', 42],
            'integer empty value' => ['toInteger', '', 0],
            'integer invalid value' => ['toInteger', 'x12', 0],

            'float numeric value' => ['toFloat', '3.141', 3.141],
            'float invalid value' => ['toFloat', 'x1.0', 0.0],
            'float exp notation' => ['toFloat', '4.0e8', 4.0e8],

            'boolean true' => ['toBoolean', 'true', true],
            'boolean 1' => ['toBoolean', '1', true],
            'boolean false' => ['toBoolean', 'false', false],
            'boolean 0' => ['toBoolean', '0', false],
            'boolean anything' => ['toBoolean', 'xz', false]
        ];
    }

    /**
     * @test
     * @dataProvider typeConversionExamples
     */
    public function typeConversionWorks($method, $string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->$method($string);
        self::assertSame($expected, $result);
    }

    public function stripTagsExamples()
    {
        return [
            'strip tags' => ['<a href="#">here</a>', null, 'here'],
            'strip tags with allowed tags' => ['<p><strong>important text</strong></p>', '<strong>', '<strong>important text</strong>'],
            'strip tags with multiple allowed tags' => ['<div><p><strong>important text</strong></p></div>', '<strong>, <p>', '<p><strong>important text</strong></p>']
        ];
    }

    /**
     * @test
     * @dataProvider stripTagsExamples
     */
    public function stripTagsWorks($string, $allowedTags, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->stripTags($string, $allowedTags);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function nl2brWorks()
    {
        $helper = new StringHelper();
        $result = $helper->nl2br('some' . chr(10) . 'string');
        self::assertSame('some<br />' . chr(10) . 'string', $result);
    }

    /**
     * @test
     */
    public function rawUrlEncodeWorks()
    {
        $helper = new StringHelper();
        $result = $helper->rawUrlEncode('&foo|bar');
        self::assertSame('%26foo%7Cbar', $result);
    }

    public function htmlSpecialCharsExamples()
    {
        return [
            'encode entities' => ['Foo &amp; Bar', null, 'Foo &amp;amp; Bar'],
            'preserve entities' => ['Foo &amp; <a href="#">Bar</a>', true, 'Foo &amp; &lt;a href="#"&gt;Bar&lt;/a&gt;']
        ];
    }

    /**
     * @test
     * @dataProvider htmlSpecialCharsExamples
     */
    public function htmlSpecialCharsWorks($string, $preserveEntities, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->htmlSpecialChars($string, $preserveEntities);
        self::assertSame($expected, $result);
    }

    public function cropExamples()
    {
        return [
            'standard options' => [
                'methodName' => 'crop',
                'maximumCharacters' => 18,
                'suffixString' => '...',
                'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
                'expected' => 'Kasper Skårhøj imp...'
            ],
            'crop at word' => [
                'methodName' => 'cropAtWord',
                'maximumCharacters' => 18,
                'suffixString' => '...',
                'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
                'expected' => 'Kasper Skårhøj ...'
            ],
            'crop at sentence' => [
                'methodName' => 'cropAtSentence',
                'maximumCharacters' => 80,
                'suffixString' => '...',
                'text' => 'Kasper Skårhøj implemented the original version of the crop function. But now we are using a TextIterator. Not too bad either.',
                'expected' => 'Kasper Skårhøj implemented the original version of the crop function. ...'
            ],
            'prefixCanBeChanged' => [
                'methodName' => 'crop',
                'maximumCharacters' => 15,
                'suffixString' => '!',
                'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
                'expected' => 'Kasper Skårhøj !'
            ],
            'subject is not modified if run without options' => [
                'methodName' => 'crop',
                'maximumCharacters' => null,
                'suffixString' => null,
                'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
                'expected' => 'Kasper Skårhøj implemented the original version of the crop function.'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider cropExamples
     */
    public function cropWorks($methodName, $maximumCharacters, $suffixString, $text, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->$methodName($text, $maximumCharacters, $suffixString);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function md5Works()
    {
        $helper = new StringHelper();
        $result = $helper->md5('joh316');
        self::assertSame('bacb98acf97e0b6112b1d1b650b84971', $result);
    }

    /**
     * @test
     */
    public function sha1Works()
    {
        $helper = new StringHelper();
        $result = $helper->sha1('joh316');
        self::assertSame('063b3d108bed9f88fa618c6046de0dccadcf3158', $result);
    }

    public function lengthExamples()
    {
        return [
            'null' => [null, 0],
            'empty' => ['', 0],
            'non-empty' => ['Foo', 3],
            'UTF-8' => ['Cäche Flüsh', 11]
        ];
    }

    /**
     * @test
     * @dataProvider lengthExamples
     */
    public function lengthWorks($input, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->length($input);
        self::assertSame($expected, $result);
    }

    public function wordCountExamples()
    {
        return [
            'null' => [null, 0],
            'empty' => ['', 0],
            'non-empty' =>
                [
                    'Hello	  	fri3nd,	you\'re
                    looking          good 	 tod@y!', 6
                ],
            'UTF-8' => ['Cäche Flüsh', 2]
        ];
    }

    /**
     * @test
     * @dataProvider wordCountExamples
     */
    public function wordCountWorks($input, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->wordCount($input);
        self::assertSame($expected, $result);
    }

    public function base64encodeEncodesDataProvider()
    {
        return [
            'empty string' => ['input' => '', 'expectedResult' => ''],
            'simple string' => ['input' => 'Flow rocks', 'expectedResult' => 'RmxvdyByb2Nrcw=='],
            'special characters' => ['input' => 'Flow röckß', 'expectedResult' => 'RmxvdyByw7Zja8Of'],
            'integer' => ['input' => 123, 'expectedResult' => 'MTIz'],
            'Stringable object' => ['input' => new TestObject(), 'expectedResult' => 'VGVzdCBPYmplY3Q='],
        ];
    }

    /**
     * @param mixed $input
     * @param string|bool $expectedResult
     * @test
     * @dataProvider base64encodeEncodesDataProvider
     */
    public function base64encodeEncodesTests($input, $expectedResult)
    {
        $helper = new StringHelper();
        self::assertSame($expectedResult, $helper->base64encode($input));
    }

    public function base64decodeEncodesDataProvider()
    {
        return [
            'empty string' => ['input' => '', 'expectedResult' => ''],
            'simple string' => ['input' => 'RmxvdyByb2Nrcw==', 'expectedResult' => 'Flow rocks'],
            'special characters' => ['input' => 'RmxvdyByw7Zja8Of', 'expectedResult' => 'Flow röckß'],
            'integer' => ['input' => 'MTIz', 'expectedResult' => '123'],
        ];
    }

    /**
     * @param mixed $input
     * @param string|bool $expectedResult
     * @test
     * @dataProvider base64decodeEncodesDataProvider
     */
    public function base64decodeEncodesTests($input, $expectedResult)
    {
        $helper = new StringHelper();
        self::assertSame($expectedResult, $helper->base64decode($input));
    }

    /**
     * @test
     */
    public function base64decodeReturnsFalseIfGivenStringIsInvalidAndStrictModeIsSet()
    {
        $helper = new StringHelper();
        self::assertFalse($helper->base64decode('invälid input', true));
    }
}

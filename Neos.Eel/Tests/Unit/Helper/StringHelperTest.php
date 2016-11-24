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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
    }

    public function pregReplaceExamples()
    {
        return [
            'replace non-alphanumeric characters' => ['Some.String with sp:cial characters', '/[[:^alnum:]]/', '-', 'Some-String-with-sp-cial-characters'],
            'no match' => ['canal', '/x/', 'y', 'canal'],
            'unicode replacement' => ['Öaßaü', '/aßa/', 'g', 'Ögü'],
            'references' => ['2016-08-31', '/([0-9]+)-([0-9]+)-([0-9]+)/', '$3.$2.$1', '31.08.2016']
        ];
    }

    /**
     * @test
     * @dataProvider pregReplaceExamples
     */
    public function pregReplaceWorks($string, $pattern, $replace, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregReplace($string, $pattern, $replace);
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function rawUrlEncodeWorks()
    {
        $helper = new StringHelper();
        $result = $helper->rawUrlEncode('&foo|bar');
        $this->assertSame('%26foo%7Cbar', $result);
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
        $this->assertSame($expected, $result);
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
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function md5Works()
    {
        $helper = new StringHelper();
        $result = $helper->md5('joh316');
        $this->assertSame('bacb98acf97e0b6112b1d1b650b84971', $result);
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
        $this->assertSame($expected, $result);
    }
}

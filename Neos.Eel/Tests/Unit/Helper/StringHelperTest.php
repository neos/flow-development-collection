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
use Neos\Eel\Helper\StringHelper;
use Neos\Eel\Tests\Unit\Fixtures\TestObject;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for StringHelper
 */
final class StringHelperTest extends UnitTestCase
{
    public static function substrExamples(): \Iterator
    {
        yield 'positive start and length lower count' => ['Hello, World!', 7, 5, 'World'];
        yield 'start equal to count' => ['Foo', 3, 42, ''];
        yield 'start greater than count' => ['Foo', 42, 5, ''];
        yield 'start negative' => ['Hello, World!', -6, 5, 'World'];
        yield 'start negative larger than abs(count)' => ['Hello, World!', -42, 5, 'Hello'];
        yield 'start positive and length omitted' => ['Hello, World!', 7, null, 'World!'];
        yield 'start positive and length is 0' => ['Hello, World!', 7, 0, ''];
        yield 'start positive and length is negative' => ['Hello, World!', 7, -1, ''];
        yield 'unicode content is extracted' => ['Öaßaä', 2, 1, 'ß'];
    }

    #[DataProvider('substrExamples')]
    #[Test]
    public function substrWorks($string, $start, $length, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->substr($string, $start, $length);
        self::assertSame($expected, $result);
    }

    public static function substringExamples(): \Iterator
    {
        yield 'start equals end' => ['Hello, World!', 7, 7, ''];
        yield 'end omitted' => ['Hello, World!', 7, null, 'World!'];
        yield 'negative start' => ['Hello, World!', -7, null, 'Hello, World!'];
        yield 'negative end' => ['Hello, World!', 5, -5, 'Hello'];
        yield 'start greater than end' => ['Hello, World!', 5, 0, 'Hello'];
        yield 'start greater than count' => ['Hello, World!', 15, 0, 'Hello, World!'];
        yield 'end greater than count' => ['Hello, World!', 7, 15, 'World!'];
        yield 'unicode content is extracted' => ['Öaßaä', 2, 3, 'ß'];
    }

    #[DataProvider('substringExamples')]
    #[Test]
    public function substringWorks($string, $start, $end, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->substring($string, $start, $end);
        self::assertSame($expected, $result);
    }

    public static function charAtExamples(): \Iterator
    {
        yield 'index in string' => ['Hello, World!', 5, ','];
        yield 'index greater than count' => ['Hello, World!', 42, ''];
        yield 'index negative' => ['Hello, World!', -1, ''];
        yield 'unicode content can be accessed' => ['Öaßaü', 2, 'ß'];
    }

    #[DataProvider('charAtExamples')]
    #[Test]
    public function charAtWorks($string, $index, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->charAt($string, $index);
        self::assertSame($expected, $result);
    }

    public static function endsWithExamples(): \Iterator
    {
        yield 'search matched' => ['To be, or not to be, that is the question.', 'question.', null, true];
        yield 'search not matched' => ['To be, or not to be, that is the question.', 'to be', null, false];
        yield 'search with position' => ['To be, or not to be, that is the question.', 'to be', 19, true];
        yield 'unicode content can be searched' => ['Öaßaü', 'aü', null, true];
    }

    #[DataProvider('endsWithExamples')]
    #[Test]
    public function endsWithWorks($string, $search, $position, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->endsWith($string, $search, $position);
        self::assertSame($expected, $result);
    }

    public static function chrExamples(): \Iterator
    {
        yield ['value' => 65, 'expected' => 'A'];
        yield ['value' => 256, 'expected' => chr(256)];
        yield ['value' => 0, 'expected' => chr(0)];
    }

    #[DataProvider('chrExamples')]
    #[Test]
    public function chrWorks($value, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->chr($value);
        self::assertSame($expected, $result);
    }

    public static function ordExamples(): \Iterator
    {
        yield ['value' => 'A', 'expected' => 65];
        yield ['value' => '', 'expected' => 0];
        yield ['value' => 1, 'expected' => 49];
        yield ['value' => 'longer string', 'expected' => 108];
    }

    #[DataProvider('ordExamples')]
    #[Test]
    public function ordWorks($value, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->ord($value);
        self::assertSame($expected, $result);
    }

    public static function indexOfExamples(): \Iterator
    {
        yield 'match at start' => ['Blue Whale', 'Blue', null, 0];
        yield 'no match' => ['Blute', 'Blue', null, -1];
        yield 'from index at start' => ['Blue Whale', 'Whale', 0, 5];
        yield 'from index at begin of match' => ['Blue Whale', 'Whale', 5, 5];
        yield 'from index after match' => ['Blue Whale', 'Whale', 6, -1];
        yield 'empty search' => ['Blue Whale', '', null, 0];
        yield 'empty search with from index' => ['Blue Whale', '', 9, 9];
        yield 'empty search with from index larger than count' => ['Blue Whale', '', 11, 10];
        yield 'case sensitive match' => ['Blue Whale', 'blue', null, -1];
        yield 'unicode content is matched' => ['Öaßaü', 'ßa', null, 2];
    }

    #[DataProvider('indexOfExamples')]
    #[Test]
    public function indexOfWorks($string, $search, $fromIndex, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->indexOf($string, $search, $fromIndex);
        self::assertSame($expected, $result);
    }

    public static function lastIndexOfExamples(): \Iterator
    {
        yield 'match last occurence' => ['canal', 'a', null, 3];
        yield 'match with from index' => ['canal', 'a', 2, 1];
        yield 'no match with from index too low' => ['canal', 'a', 0, -1];
        yield 'no match' => ['canal', 'x', null, -1];
        yield 'unicode content is matched' => ['Öaßaü', 'a', null, 3];
    }

    #[DataProvider('lastIndexOfExamples')]
    #[Test]
    public function lastIndexOfWorks($string, $search, $fromIndex, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->lastIndexOf($string, $search, $fromIndex);
        self::assertSame($expected, $result);
    }

    public static function pregMatchExamples(): \Iterator
    {
        yield 'matches' => ['For more information, see Chapter 3.4.5.1', '/(chapter \d+(\.\d)*)/i', ['Chapter 3.4.5.1', 'Chapter 3.4.5.1', '.1']];
    }

    #[DataProvider('pregMatchExamples')]
    #[Test]
    public function pregMatchWorks($string, $pattern, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregMatch($string, $pattern);
        self::assertSame($expected, $result);
    }

    public static function pregMatchAllExamples(): \Iterator
    {
        yield 'matches' => ['<hr id="icon-one" /><hr id="icon-two" />', '/id="icon-(.+?)"/', [['id="icon-one"', 'id="icon-two"'],['one','two']]];
    }

    #[DataProvider('pregMatchAllExamples')]
    #[Test]
    public function pregMatchAllWorks($string, $pattern, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregMatchAll($string, $pattern);
        self::assertSame($expected, $result);
    }

    public static function pregReplaceExamples(): \Iterator
    {
        yield 'replace non-alphanumeric characters' => ['Some.String with sp:cial characters', '/[[:^alnum:]]/', '-', null, 'Some-String-with-sp-cial-characters'];
        yield 'replace non-alphanumeric characters width limit' => ['Some.String with sp:cial characters', '/[[:^alnum:]]/', '-', 1, 'Some-String with sp:cial characters'];
        yield 'no match' => ['canal', '/x/', 'y', null, 'canal'];
        yield 'unicode replacement' => ['Öaßaü', '/aßa/', 'g', null, 'Ögü'];
        yield 'references' => ['2016-08-31', '/([0-9]+)-([0-9]+)-([0-9]+)/', '$3.$2.$1', null, '31.08.2016'];
    }

    #[DataProvider('pregReplaceExamples')]
    #[Test]
    public function pregReplaceWorks($string, $pattern, $replace, $limit, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregReplace($string, $pattern, $replace, $limit);
        self::assertSame($expected, $result);
    }

    public static function pregSplitExamples(): \Iterator
    {
        yield 'matches' => ['foo bar   baz', '/\s+/', -1, ['foo', 'bar', 'baz']];
        yield 'matches with limit' => ['first second third', '/\s+/', 2, ['first', 'second third']];
    }

    #[DataProvider('pregSplitExamples')]
    #[Test]
    public function pregMSplitWorks($string, $pattern, $limit, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->pregSplit($string, $pattern, $limit);
        self::assertSame($expected, $result);
    }

    public static function replaceExamples(): \Iterator
    {
        yield 'replace' => ['canal', 'ana', 'oo', 'cool'];
        yield 'replace-array' => ['cool gridge', ['oo', 'gri'], ['ana', 'bri'], 'canal bridge'];
        yield 'no match' => ['canal', 'x', 'y', 'canal'];
        yield 'unicode replacement' => ['Öaßaü', 'aßa', 'g', 'Ögü'];
    }

    #[DataProvider('replaceExamples')]
    #[Test]
    public function replaceWorks($string, $search, $replace, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->replace($string, $search, $replace);
        self::assertSame($expected, $result);
    }


    public static function splitExamples(): \Iterator
    {
        yield 'split' => ['My hovercraft is full of eels', ' ', null, ['My', 'hovercraft', 'is', 'full', 'of', 'eels']];
        yield 'NULL separator' => ['The bad parts', null, null, ['The bad parts']];
        yield 'empty separator' => ['Foo', '', null, ['F', 'o', 'o']];
        yield 'empty separator with limit' => ['Foo', '', 2, ['F', 'o']];
    }

    #[DataProvider('splitExamples')]
    #[Test]
    public function splitWorks($string, $separator, $limit, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->split($string, $separator, $limit);
        self::assertSame($expected, $result);
    }

    public static function startsWithExamples(): \Iterator
    {
        yield 'search matched' => ['To be, or not to be, that is the question.', 'To be', null, true];
        yield 'search not matched' => ['To be, or not to be, that is the question.', 'not to be', null, false];
        yield 'search with position' => ['To be, or not to be, that is the question.', 'that is', 21, true];
        yield 'search with duplicate match' => ['to be, or not to be, that is the question.', 'to be', null, true];
        yield 'unicode content can be searched' => ['Öaßaü', 'Öa', null, true];
    }

    #[DataProvider('startsWithExamples')]
    #[Test]
    public function startsWithWorks($string, $search, $position, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->startsWith($string, $search, $position);
        self::assertSame($expected, $result);
    }

    public static function firstLetterToUpperCaseExamples(): \Iterator
    {
        yield 'lowercase' => ['foo', 'Foo'];
        yield 'firstLetterUpperCase' => ['Foo', 'Foo'];
    }

    #[DataProvider('firstLetterToUpperCaseExamples')]
    #[Test]
    public function firstLetterToUpperCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->firstLetterToUpperCase($string);
        self::assertSame($expected, $result);
    }

    public static function firstLetterToLowerCaseExamples(): \Iterator
    {
        yield 'lowercase' => ['foo', 'foo'];
        yield 'firstLetterUpperCase' => ['Foo', 'foo'];
    }

    #[DataProvider('firstLetterToLowerCaseExamples')]
    #[Test]
    public function firstLetterToLowerCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->firstLetterToLowerCase($string);
        self::assertSame($expected, $result);
    }

    public static function toLowerCaseExamples(): \Iterator
    {
        yield 'lowercase' => ['Foo bAr BaZ', 'foo bar baz'];
    }

    #[DataProvider('toLowerCaseExamples')]
    #[Test]
    public function toLowerCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->toLowerCase($string);
        self::assertSame($expected, $result);
    }

    public static function toUpperCaseExamples(): \Iterator
    {
        yield 'uppercase' => ['Foo bAr BaZ', 'FOO BAR BAZ'];
    }

    #[DataProvider('toUpperCaseExamples')]
    #[Test]
    public function toUpperCaseWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->toUpperCase($string);
        self::assertSame($expected, $result);
    }

    public static function isBlankExamples(): \Iterator
    {
        yield 'string with whitespace' => ['  	', true];
        yield 'string with characters' => [' abc ', false];
        yield 'empty string' => ['', true];
        yield 'NULL string' => [null, true];
    }

    #[DataProvider('isBlankExamples')]
    #[Test]
    public function isBlankWorks($string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->isBlank($string);
        self::assertSame($expected, $result);
    }

    public static function trimExamples(): \Iterator
    {
        yield 'string with whitespace' => ['  	', null, ''];
        yield 'string with characters and whitespace' => [" Foo Bar \n", null, 'Foo Bar'];
        yield 'empty string' => ['', null, ''];
        yield 'trim with charlist' => ['< abc >', '<>', ' abc '];
    }

    #[DataProvider('trimExamples')]
    #[Test]
    public function trimWorks($string, $charlist, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->trim($string, $charlist);
        self::assertSame($expected, $result);
    }

    public static function typeConversionExamples(): \Iterator
    {
        yield 'string numeric value' => ['toString', 42, '42'];
        yield 'string true boolean value' => ['toString', true, '1'];
        yield 'string false boolean value' => ['toString', false, ''];
        yield 'integer numeric value' => ['toInteger', '42', 42];
        yield 'integer empty value' => ['toInteger', '', 0];
        yield 'integer invalid value' => ['toInteger', 'x12', 0];
        yield 'float numeric value' => ['toFloat', '3.141', 3.141];
        yield 'float invalid value' => ['toFloat', 'x1.0', 0.0];
        yield 'float exp notation' => ['toFloat', '4.0e8', 4.0e8];
        yield 'boolean true' => ['toBoolean', 'true', true];
        yield 'boolean 1' => ['toBoolean', '1', true];
        yield 'boolean false' => ['toBoolean', 'false', false];
        yield 'boolean 0' => ['toBoolean', '0', false];
        yield 'boolean anything' => ['toBoolean', 'xz', false];
    }

    #[DataProvider('typeConversionExamples')]
    #[Test]
    public function typeConversionWorks($method, $string, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->$method($string);
        self::assertSame($expected, $result);
    }

    public static function stripTagsExamples(): \Iterator
    {
        yield 'strip tags' => ['<a href="#">here</a>', null, 'here'];
        yield 'strip tags with allowed tags' => ['<p><strong>important text</strong></p>', '<strong>', '<strong>important text</strong>'];
        yield 'strip tags with multiple allowed tags' => ['<div><p><strong>important text</strong></p></div>', '<strong>, <p>', '<p><strong>important text</strong></p>'];
    }

    #[DataProvider('stripTagsExamples')]
    #[Test]
    public function stripTagsWorks($string, $allowedTags, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->stripTags($string, $allowedTags);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function nl2brWorks()
    {
        $helper = new StringHelper();
        $result = $helper->nl2br('some' . chr(10) . 'string');
        self::assertSame('some<br />' . chr(10) . 'string', $result);
    }

    #[Test]
    public function rawUrlEncodeWorks()
    {
        $helper = new StringHelper();
        $result = $helper->rawUrlEncode('&foo|bar');
        self::assertSame('%26foo%7Cbar', $result);
    }

    public static function htmlSpecialCharsExamples(): \Iterator
    {
        yield 'encode entities' => ['Foo &amp; Bar', false, 'Foo &amp;amp; Bar'];
        yield 'preserve entities' => ['Foo &amp; <a href="#">Bar</a>', true, 'Foo &amp; &lt;a href="#"&gt;Bar&lt;/a&gt;'];
    }

    #[DataProvider('htmlSpecialCharsExamples')]
    #[Test]
    public function htmlSpecialCharsWorks($string, $preserveEntities, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->htmlSpecialChars($string, $preserveEntities);
        self::assertSame($expected, $result);
    }

    public static function cropExamples(): \Iterator
    {
        yield 'standard options' => [
            'methodName' => 'crop',
            'maximumCharacters' => 18,
            'suffixString' => '...',
            'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
            'expected' => 'Kasper Skårhøj imp...'
        ];
        yield 'crop at word' => [
            'methodName' => 'cropAtWord',
            'maximumCharacters' => 18,
            'suffixString' => '...',
            'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
            'expected' => 'Kasper Skårhøj ...'
        ];
        yield 'crop at sentence' => [
            'methodName' => 'cropAtSentence',
            'maximumCharacters' => 80,
            'suffixString' => '...',
            'text' => 'Kasper Skårhøj implemented the original version of the crop function. But now we are using a TextIterator. Not too bad either.',
            'expected' => 'Kasper Skårhøj implemented the original version of the crop function. ...'
        ];
        yield 'prefixCanBeChanged' => [
            'methodName' => 'crop',
            'maximumCharacters' => 15,
            'suffixString' => '!',
            'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
            'expected' => 'Kasper Skårhøj !'
        ];
        yield 'subject is not modified if run without options' => [
            'methodName' => 'crop',
            'maximumCharacters' => null,
            'suffixString' => '',
            'text' => 'Kasper Skårhøj implemented the original version of the crop function.',
            'expected' => 'Kasper Skårhøj implemented the original version of the crop function.'
        ];
    }

    #[DataProvider('cropExamples')]
    #[Test]
    public function cropWorks($methodName, $maximumCharacters, $suffixString, $text, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->$methodName($text, $maximumCharacters, $suffixString);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function md5Works()
    {
        $helper = new StringHelper();
        $result = $helper->md5('joh316');
        self::assertSame('bacb98acf97e0b6112b1d1b650b84971', $result);
    }

    #[Test]
    public function sha1Works()
    {
        $helper = new StringHelper();
        $result = $helper->sha1('joh316');
        self::assertSame('063b3d108bed9f88fa618c6046de0dccadcf3158', $result);
    }

    public static function lengthExamples(): \Iterator
    {
        yield 'null' => [null, 0];
        yield 'empty' => ['', 0];
        yield 'non-empty' => ['Foo', 3];
        yield 'UTF-8' => ['Cäche Flüsh', 11];
    }

    #[DataProvider('lengthExamples')]
    #[Test]
    public function lengthWorks($input, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->length($input);
        self::assertSame($expected, $result);
    }

    public static function wordCountExamples(): \Iterator
    {
        yield 'null' => [null, 0];
        yield 'empty' => ['', 0];
        yield 'non-empty' => [
            'Hello	  	fri3nd,	you\'re
                    looking          good 	 tod@y!', 6
        ];
        yield 'UTF-8' => ['Cäche Flüsh', 2];
    }

    #[DataProvider('wordCountExamples')]
    #[Test]
    public function wordCountWorks($input, $expected)
    {
        $helper = new StringHelper();
        $result = $helper->wordCount($input);
        self::assertSame($expected, $result);
    }

    public static function base64encodeEncodesDataProvider(): \Iterator
    {
        yield 'empty string' => ['input' => '', 'expectedResult' => ''];
        yield 'simple string' => ['input' => 'Flow rocks', 'expectedResult' => 'RmxvdyByb2Nrcw=='];
        yield 'special characters' => ['input' => 'Flow röckß', 'expectedResult' => 'RmxvdyByw7Zja8Of'];
        yield 'integer' => ['input' => 123, 'expectedResult' => 'MTIz'];
        yield 'Stringable object' => ['input' => new TestObject(), 'expectedResult' => 'VGVzdCBPYmplY3Q='];
    }

    /**
     * @param mixed $input
     * @param string|bool $expectedResult
     */
    #[DataProvider('base64encodeEncodesDataProvider')]
    #[Test]
    public function base64encodeEncodesTests($input, $expectedResult)
    {
        $helper = new StringHelper();
        self::assertSame($expectedResult, $helper->base64encode($input));
    }

    public static function base64decodeEncodesDataProvider(): \Iterator
    {
        yield 'empty string' => ['input' => '', 'expectedResult' => ''];
        yield 'simple string' => ['input' => 'RmxvdyByb2Nrcw==', 'expectedResult' => 'Flow rocks'];
        yield 'special characters' => ['input' => 'RmxvdyByw7Zja8Of', 'expectedResult' => 'Flow röckß'];
        yield 'integer' => ['input' => 'MTIz', 'expectedResult' => '123'];
    }

    /**
     * @param mixed $input
     * @param string|bool $expectedResult
     */
    #[DataProvider('base64decodeEncodesDataProvider')]
    #[Test]
    public function base64decodeEncodesTests($input, $expectedResult)
    {
        $helper = new StringHelper();
        self::assertSame($expectedResult, $helper->base64decode($input));
    }

    #[Test]
    public function base64decodeReturnsFalseIfGivenStringIsInvalidAndStrictModeIsSet()
    {
        $helper = new StringHelper();
        self::assertFalse($helper->base64decode('invälid input', true));
    }
}

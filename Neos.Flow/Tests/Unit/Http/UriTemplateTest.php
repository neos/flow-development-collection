<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Http\UriTemplate;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the UriTemplate class
 *
 */
final class UriTemplateTest extends UnitTestCase
{
    /**
     * Uri template strings
     */
    public static function templateStrings(): \Iterator
    {
        $variables1 = ['var' => 'value', 'hello' => 'Hello World!'];
        $variables2 = ['var' => 'value', 'hello' => 'Hello World!', 'path' => '/foo/bar'];
        $variables3 = ['var' => 'value', 'hello' => 'Hello World!', 'empty' => '', 'path' => '/foo/bar', 'x' => 1024, 'y' => 768];
        $variables4 = ['var' => 'value', 'hello' => 'Hello World!', 'path' => '/foo/bar', 'list' => ['red', 'green', 'blue'], 'keys' => ['semi' => ';', 'dot' => '.', 'comma' => ',']];
        // examples from RFC 6570 introduction
        yield ['http://example.com/~{username}/', ['username' => 'fred'], 'http://example.com/~fred/'];
        yield ['http://example.com/dictionary/{term:1}/{term}', ['term' => 'cat'], 'http://example.com/dictionary/c/cat'];
        yield ['http://example.com/search{?q,lang}', ['q' => 'chien', 'lang' => 'fr'], 'http://example.com/search?q=chien&lang=fr'];
        yield ['http://example.com/search{?q,lang}', ['q' => 'chien'], 'http://example.com/search?q=chien'];
        yield ['http://example.com/search{?q,lang}', ['lang' => 'fr'], 'http://example.com/search?lang=fr'];
        yield ['http://example.com/search{?q,lang}', [], 'http://example.com/search'];
        // level 1 examples from RFC 6570
        yield ['{var}', $variables1, 'value'];
        yield ['{hello}', $variables1, 'Hello%20World%21'];
        // level 2 examples from RFC 6570
        yield ['{var}', $variables2, 'value'];
        yield ['{+hello}', $variables2, 'Hello%20World!'];
        yield ['{+path}/here', $variables2, '/foo/bar/here'];
        yield ['?ref={+path}', $variables2, '?ref=/foo/bar'];
        yield ['{#var}', $variables2, '#value'];
        yield ['{#hello}', $variables2, '#Hello%20World!'];
        // level 3 examples from RFC 6570
        yield ['/map?{x,y}', $variables3, '/map?1024,768'];
        yield ['{x,hello,y}', $variables3, '1024,Hello%20World%21,768'];
        yield ['{+x,hello,y}', $variables3, '1024,Hello%20World!,768'];
        yield ['{#x,hello,y}', $variables3, '#1024,Hello%20World!,768'];
        yield ['{#path,x}/here', $variables3, '#/foo/bar,1024/here'];
        yield ['X{.var}', $variables3, 'X.value'];
        yield ['X{.x,y}', $variables3, 'X.1024.768'];
        yield ['{/var}', $variables3, '/value'];
        yield ['{/var,x}/here', $variables3, '/value/1024/here'];
        yield ['{;x,y}', $variables3, ';x=1024;y=768'];
        yield ['{;x,y,empty}', $variables3, ';x=1024;y=768;empty'];
        yield ['{?x,y}', $variables3, '?x=1024&y=768'];
        yield ['{?x,y,empty}', $variables3, '?x=1024&y=768&empty='];
        yield ['?fixed=yes{&x}', $variables3, '?fixed=yes&x=1024'];
        yield ['{&x,y,empty}', $variables3, '&x=1024&y=768&empty='];
        // level 4 examples from RFC 6570
        yield ['{var:3}', $variables4, 'val'];
        yield ['{var:30}', $variables4, 'value'];
        yield ['{list}', $variables4, 'red,green,blue'];
        yield ['{list*}', $variables4, 'red,green,blue'];
        yield ['{keys}', $variables4, 'semi,%3B,dot,.,comma,%2C'];
        yield ['{keys*}', $variables4, 'semi=%3B,dot=.,comma=%2C'];
        yield ['{+path:6}/here', $variables4, '/foo/b/here'];
        yield ['{+list}', $variables4, 'red,green,blue'];
        yield ['{+list*}', $variables4, 'red,green,blue'];
        yield ['{+keys}', $variables4, 'semi,;,dot,.,comma,,'];
        yield ['{+keys*}', $variables4, 'semi=;,dot=.,comma=,'];
        yield ['{#path:6}/here', $variables4, '#/foo/b/here'];
        yield ['{#list}', $variables4, '#red,green,blue'];
        yield ['{#list*}', $variables4, '#red,green,blue'];
        yield ['{#keys}', $variables4, '#semi,;,dot,.,comma,,'];
        yield ['{#keys*}', $variables4, '#semi=;,dot=.,comma=,'];
        yield ['X{.var:3}', $variables4, 'X.val'];
        yield ['X{.list}', $variables4, 'X.red,green,blue'];
        yield ['X{.list*}', $variables4, 'X.red.green.blue'];
        yield ['X{.keys}', $variables4, 'X.semi,%3B,dot,.,comma,%2C'];
        yield ['X{.keys*}', $variables4, 'X.semi=%3B.dot=..comma=%2C'];
        yield ['{/var:1,var}', $variables4, '/v/value'];
        yield ['{/list}', $variables4, '/red,green,blue'];
        yield ['{/list*}', $variables4, '/red/green/blue'];
        yield ['{/list*,path:4}', $variables4, '/red/green/blue/%2Ffoo'];
        yield ['{/keys}', $variables4, '/semi,%3B,dot,.,comma,%2C'];
        yield ['{/keys*}', $variables4, '/semi=%3B/dot=./comma=%2C'];
        yield ['{;hello:5}', $variables4, ';hello=Hello'];
        yield ['{;list}', $variables4, ';list=red,green,blue'];
        yield ['{;list*}', $variables4, ';list=red;list=green;list=blue'];
        yield ['{;keys}', $variables4, ';keys=semi,%3B,dot,.,comma,%2C'];
        yield ['{;keys*}', $variables4, ';semi=%3B;dot=.;comma=%2C'];
        yield ['{?var:3}', $variables4, '?var=val'];
        yield ['{?list}', $variables4, '?list=red,green,blue'];
        yield ['{?list*}', $variables4, '?list=red&list=green&list=blue'];
        yield ['{?keys}', $variables4, '?keys=semi,%3B,dot,.,comma,%2C'];
        yield ['{?keys*}', $variables4, '?semi=%3B&dot=.&comma=%2C'];
        yield ['{&var:3}', $variables4, '&var=val'];
        yield ['{&list}', $variables4, '&list=red,green,blue'];
        yield ['{&list*}', $variables4, '&list=red&list=green&list=blue'];
        yield ['{&keys}', $variables4, '&keys=semi,%3B,dot,.,comma,%2C'];
        yield ['{&keys*}', $variables4, '&semi=%3B&dot=.&comma=%2C'];
        // cases uncovered so far
        yield ['', [], ''];
        yield ['/foo/bar', [], '/foo/bar'];
        yield ['an/empty/{?list}', ['list' => []], 'an/empty/'];
        yield ['a?nested{&list*}', ['list' => ['red' => 'rouge', 'green' => ['blue', 'mountain']]], 'a?nested&red=rouge&green%5B0%5D=blue&green%5B1%5D=mountain'];
        yield ['associative?nested{&list*}', ['list' => ['red' => 'rouge', 'green' => ['blue' => 'mountain']]], 'associative?nested&red=rouge&green%5Bblue%5D=mountain'];
    }

    #[DataProvider('templateStrings')]
    #[Test]
    public function uriTemplatesAreExpandedCorrectly($templateString, array $variables, $expectedString)
    {
        $expandedTemplate = UriTemplate::expand($templateString, $variables);
        self::assertEquals($expectedString, $expandedTemplate);
    }
}

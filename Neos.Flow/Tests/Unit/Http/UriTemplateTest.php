<?php
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

/**
 * Testcase for the UriTemplate class
 *
 */
class UriTemplateTest extends UnitTestCase
{
    /**
     * Uri template strings
     */
    public function templateStrings()
    {
        $variables1 = ['var' => 'value', 'hello' => 'Hello World!'];
        $variables2 = ['var' => 'value', 'hello' => 'Hello World!', 'path' => '/foo/bar'];
        $variables3 = ['var' => 'value', 'hello' => 'Hello World!', 'empty' => '', 'path' => '/foo/bar', 'x' => 1024, 'y' => 768];
        $variables4 = ['var' => 'value', 'hello' => 'Hello World!', 'path' => '/foo/bar', 'list' => ['red', 'green', 'blue'], 'keys' => ['semi' => ';', 'dot' => '.', 'comma' => ',']];

        return [
            // examples from RFC 6570 introduction
            ['http://example.com/~{username}/', ['username' => 'fred'], 'http://example.com/~fred/'],
            ['http://example.com/dictionary/{term:1}/{term}', ['term' => 'cat'], 'http://example.com/dictionary/c/cat'],
            ['http://example.com/search{?q,lang}', ['q' => 'chien', 'lang' => 'fr'], 'http://example.com/search?q=chien&lang=fr'],
            ['http://example.com/search{?q,lang}', ['q' => 'chien'], 'http://example.com/search?q=chien'],
            ['http://example.com/search{?q,lang}', ['lang' => 'fr'], 'http://example.com/search?lang=fr'],
            ['http://example.com/search{?q,lang}', [], 'http://example.com/search'],

            // level 1 examples from RFC 6570
            ['{var}', $variables1, 'value'],
            ['{hello}', $variables1, 'Hello%20World%21'],

            // level 2 examples from RFC 6570
            ['{var}', $variables2, 'value'],
            ['{+hello}', $variables2, 'Hello%20World!'],
            ['{+path}/here', $variables2, '/foo/bar/here'],
            ['?ref={+path}', $variables2, '?ref=/foo/bar'],
            ['{#var}', $variables2, '#value'],
            ['{#hello}', $variables2, '#Hello%20World!'],

            // level 3 examples from RFC 6570
            ['/map?{x,y}', $variables3, '/map?1024,768'],
            ['{x,hello,y}', $variables3, '1024,Hello%20World%21,768'],
            ['{+x,hello,y}', $variables3, '1024,Hello%20World!,768'],
            ['{#x,hello,y}', $variables3, '#1024,Hello%20World!,768'],
            ['{#path,x}/here', $variables3, '#/foo/bar,1024/here'],
            ['X{.var}', $variables3, 'X.value'],
            ['X{.x,y}', $variables3, 'X.1024.768'],
            ['{/var}', $variables3, '/value'],
            ['{/var,x}/here', $variables3, '/value/1024/here'],
            ['{;x,y}', $variables3, ';x=1024;y=768'],
            ['{;x,y,empty}', $variables3, ';x=1024;y=768;empty'],
            ['{?x,y}', $variables3, '?x=1024&y=768'],
            ['{?x,y,empty}', $variables3, '?x=1024&y=768&empty='],
            ['?fixed=yes{&x}', $variables3, '?fixed=yes&x=1024'],
            ['{&x,y,empty}', $variables3, '&x=1024&y=768&empty='],

            // level 4 examples from RFC 6570
            ['{var:3}', $variables4, 'val'],
            ['{var:30}', $variables4, 'value'],
            ['{list}', $variables4, 'red,green,blue'],
            ['{list*}', $variables4, 'red,green,blue'],
            ['{keys}', $variables4, 'semi,%3B,dot,.,comma,%2C'],
            ['{keys*}', $variables4, 'semi=%3B,dot=.,comma=%2C'],
            ['{+path:6}/here', $variables4, '/foo/b/here'],
            ['{+list}', $variables4, 'red,green,blue'],
            ['{+list*}', $variables4, 'red,green,blue'],
            ['{+keys}', $variables4, 'semi,;,dot,.,comma,,'],
            ['{+keys*}', $variables4, 'semi=;,dot=.,comma=,'],
            ['{#path:6}/here', $variables4, '#/foo/b/here'],
            ['{#list}', $variables4, '#red,green,blue'],
            ['{#list*}', $variables4, '#red,green,blue'],
            ['{#keys}', $variables4, '#semi,;,dot,.,comma,,'],
            ['{#keys*}', $variables4, '#semi=;,dot=.,comma=,'],
            ['X{.var:3}', $variables4, 'X.val'],
            ['X{.list}', $variables4, 'X.red,green,blue'],
            ['X{.list*}', $variables4, 'X.red.green.blue'],
            ['X{.keys}', $variables4, 'X.semi,%3B,dot,.,comma,%2C'],
            ['X{.keys*}', $variables4, 'X.semi=%3B.dot=..comma=%2C'],
            ['{/var:1,var}', $variables4, '/v/value'],
            ['{/list}', $variables4, '/red,green,blue'],
            ['{/list*}', $variables4, '/red/green/blue'],
            ['{/list*,path:4}', $variables4, '/red/green/blue/%2Ffoo'],
            ['{/keys}', $variables4, '/semi,%3B,dot,.,comma,%2C'],
            ['{/keys*}', $variables4, '/semi=%3B/dot=./comma=%2C'],
            ['{;hello:5}', $variables4, ';hello=Hello'],
            ['{;list}', $variables4, ';list=red,green,blue'],
            ['{;list*}', $variables4, ';list=red;list=green;list=blue'],
            ['{;keys}', $variables4, ';keys=semi,%3B,dot,.,comma,%2C'],
            ['{;keys*}', $variables4, ';semi=%3B;dot=.;comma=%2C'],
            ['{?var:3}', $variables4, '?var=val'],
            ['{?list}', $variables4, '?list=red,green,blue'],
            ['{?list*}', $variables4, '?list=red&list=green&list=blue'],
            ['{?keys}', $variables4, '?keys=semi,%3B,dot,.,comma,%2C'],
            ['{?keys*}', $variables4, '?semi=%3B&dot=.&comma=%2C'],
            ['{&var:3}', $variables4, '&var=val'],
            ['{&list}', $variables4, '&list=red,green,blue'],
            ['{&list*}', $variables4, '&list=red&list=green&list=blue'],
            ['{&keys}', $variables4, '&keys=semi,%3B,dot,.,comma,%2C'],
            ['{&keys*}', $variables4, '&semi=%3B&dot=.&comma=%2C'],

            // cases uncovered so far
            ['', [], ''],
            ['/foo/bar', [], '/foo/bar'],
            ['an/empty/{?list}', ['list' => []], 'an/empty/'],
            ['a?nested{&list*}', ['list' => ['red' => 'rouge', 'green' => ['blue', 'mountain']]], 'a?nested&red=rouge&green%5B0%5D=blue&green%5B1%5D=mountain'],
            ['associative?nested{&list*}', ['list' => ['red' => 'rouge', 'green' => ['blue' => 'mountain']]], 'associative?nested&red=rouge&green%5Bblue%5D=mountain'],
        ];
    }

    /**
     * @dataProvider templateStrings
     * @test
     */
    public function uriTemplatesAreExpandedCorrectly($templateString, array $variables, $expectedString)
    {
        $expandedTemplate = UriTemplate::expand($templateString, $variables);
        $this->assertEquals($expectedString, $expandedTemplate);
    }
}

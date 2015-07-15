<?php
namespace TYPO3\Flow\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\UriTemplate;

/**
 * Testcase for the UriTemplate class
 *
 */
class UriTemplateTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Uri template strings
	 */
	public function templateStrings() {
		$variables1 = array('var' => 'value', 'hello' => 'Hello World!');
		$variables2 = array('var' => 'value', 'hello' => 'Hello World!', 'path' => '/foo/bar');
		$variables3 = array('var' => 'value', 'hello' => 'Hello World!', 'empty' => '', 'path' => '/foo/bar', 'x' => 1024, 'y' => 768);
		$variables4 = array('var' => 'value', 'hello' => 'Hello World!', 'path' => '/foo/bar', 'list' => array('red', 'green', 'blue'), 'keys' => array('semi' => ';', 'dot' => '.', 'comma' => ','));

		return array(
			// examples from RFC 6570 introduction
			array('http://example.com/~{username}/', array('username' => 'fred'), 'http://example.com/~fred/'),
			array('http://example.com/dictionary/{term:1}/{term}', array('term' => 'cat'), 'http://example.com/dictionary/c/cat'),
			array('http://example.com/search{?q,lang}', array('q' => 'chien', 'lang' => 'fr'), 'http://example.com/search?q=chien&lang=fr'),
			array('http://example.com/search{?q,lang}', array('q' => 'chien'), 'http://example.com/search?q=chien'),
			array('http://example.com/search{?q,lang}', array('lang' => 'fr'), 'http://example.com/search?lang=fr'),
			array('http://example.com/search{?q,lang}', array(), 'http://example.com/search'),

			// level 1 examples from RFC 6570
			array('{var}', $variables1, 'value'),
			array('{hello}', $variables1, 'Hello%20World%21'),

			// level 2 examples from RFC 6570
			array('{var}', $variables2, 'value'),
			array('{+hello}', $variables2, 'Hello%20World!'),
			array('{+path}/here', $variables2, '/foo/bar/here'),
			array('?ref={+path}', $variables2, '?ref=/foo/bar'),
			array('{#var}', $variables2, '#value'),
			array('{#hello}', $variables2, '#Hello%20World!'),

			// level 3 examples from RFC 6570
			array('/map?{x,y}', $variables3, '/map?1024,768'),
			array('{x,hello,y}', $variables3, '1024,Hello%20World%21,768'),
			array('{+x,hello,y}', $variables3, '1024,Hello%20World!,768'),
			array('{#x,hello,y}', $variables3, '#1024,Hello%20World!,768'),
			array('{#path,x}/here', $variables3, '#/foo/bar,1024/here'),
			array('X{.var}', $variables3, 'X.value'),
			array('X{.x,y}', $variables3, 'X.1024.768'),
			array('{/var}', $variables3, '/value'),
			array('{/var,x}/here', $variables3, '/value/1024/here'),
			array('{;x,y}', $variables3, ';x=1024;y=768'),
			array('{;x,y,empty}', $variables3, ';x=1024;y=768;empty'),
			array('{?x,y}', $variables3, '?x=1024&y=768'),
			array('{?x,y,empty}', $variables3, '?x=1024&y=768&empty='),
			array('?fixed=yes{&x}', $variables3, '?fixed=yes&x=1024'),
			array('{&x,y,empty}', $variables3, '&x=1024&y=768&empty='),

			// level 4 examples from RFC 6570
			array('{var:3}', $variables4, 'val'),
			array('{var:30}', $variables4, 'value'),
			array('{list}', $variables4, 'red,green,blue'),
			array('{list*}', $variables4, 'red,green,blue'),
			array('{keys}', $variables4, 'semi,%3B,dot,.,comma,%2C'),
			array('{keys*}', $variables4, 'semi=%3B,dot=.,comma=%2C'),
			array('{+path:6}/here', $variables4, '/foo/b/here'),
			array('{+list}', $variables4, 'red,green,blue'),
			array('{+list*}', $variables4, 'red,green,blue'),
			array('{+keys}', $variables4, 'semi,;,dot,.,comma,,'),
			array('{+keys*}', $variables4, 'semi=;,dot=.,comma=,'),
			array('{#path:6}/here', $variables4, '#/foo/b/here'),
			array('{#list}', $variables4, '#red,green,blue'),
			array('{#list*}', $variables4, '#red,green,blue'),
			array('{#keys}', $variables4, '#semi,;,dot,.,comma,,'),
			array('{#keys*}', $variables4, '#semi=;,dot=.,comma=,'),
			array('X{.var:3}', $variables4, 'X.val'),
			array('X{.list}', $variables4, 'X.red,green,blue'),
			array('X{.list*}', $variables4, 'X.red.green.blue'),
			array('X{.keys}', $variables4, 'X.semi,%3B,dot,.,comma,%2C'),
			array('X{.keys*}', $variables4, 'X.semi=%3B.dot=..comma=%2C'),
			array('{/var:1,var}', $variables4, '/v/value'),
			array('{/list}', $variables4, '/red,green,blue'),
			array('{/list*}', $variables4, '/red/green/blue'),
			array('{/list*,path:4}', $variables4, '/red/green/blue/%2Ffoo'),
			array('{/keys}', $variables4, '/semi,%3B,dot,.,comma,%2C'),
			array('{/keys*}', $variables4, '/semi=%3B/dot=./comma=%2C'),
			array('{;hello:5}', $variables4, ';hello=Hello'),
			array('{;list}', $variables4, ';list=red,green,blue'),
			array('{;list*}', $variables4, ';list=red;list=green;list=blue'),
			array('{;keys}', $variables4, ';keys=semi,%3B,dot,.,comma,%2C'),
			array('{;keys*}', $variables4, ';semi=%3B;dot=.;comma=%2C'),
			array('{?var:3}', $variables4, '?var=val'),
			array('{?list}', $variables4, '?list=red,green,blue'),
			array('{?list*}', $variables4, '?list=red&list=green&list=blue'),
			array('{?keys}', $variables4, '?keys=semi,%3B,dot,.,comma,%2C'),
			array('{?keys*}', $variables4, '?semi=%3B&dot=.&comma=%2C'),
			array('{&var:3}', $variables4, '&var=val'),
			array('{&list}', $variables4, '&list=red,green,blue'),
			array('{&list*}', $variables4, '&list=red&list=green&list=blue'),
			array('{&keys}', $variables4, '&keys=semi,%3B,dot,.,comma,%2C'),
			array('{&keys*}', $variables4, '&semi=%3B&dot=.&comma=%2C'),

			// cases uncovered so far
			array('', array(), ''),
			array('/foo/bar', array(), '/foo/bar'),
			array('an/empty/{?list}', array('list' => array()), 'an/empty/'),
			array('a?nested{&list*}', array('list' => array('red' => 'rouge', 'green' => array('blue', 'mountain'))), 'a?nested&red=rouge&green%5B0%5D=blue&green%5B1%5D=mountain'),
			array('associative?nested{&list*}', array('list' => array('red' => 'rouge', 'green' => array('blue' => 'mountain'))), 'associative?nested&red=rouge&green%5Bblue%5D=mountain'),
		);
	}

	/**
	 * @dataProvider templateStrings
	 * @test
	 */
	public function uriTemplatesAreExpandedCorrectly($templateString, array $variables, $expectedString) {
		$expandedTemplate = UriTemplate::expand($templateString, $variables);
		$this->assertEquals($expectedString, $expandedTemplate);
	}
}

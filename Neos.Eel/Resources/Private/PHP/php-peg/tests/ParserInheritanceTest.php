<?php
namespace PhpPeg;

require_once "ParserTestBase.php";

class ParserInheritanceTest extends ParserTestBase {

	public function testBasicInheritance() {

		$parser = $this->buildParser('
			/*!* BasicInheritanceTestParser
			Foo: "a"
			Bar extends Foo
			*/
		');

		self::assertTrue($parser->matches('Foo', 'a'));
		self::assertTrue($parser->matches('Bar', 'a'));

		self::assertFalse($parser->matches('Foo', 'b'));
		self::assertFalse($parser->matches('Bar', 'b'));
	}

	public function testBasicInheritanceConstructFallback() {

		$parser = $this->buildParser('
			/*!* BasicInheritanceConstructFallbackParser
			Foo: "a"
				function __construct(&$res){ $res["test"] = "test"; }
			Bar extends Foo
			*/
		');

		$res = $parser->match('Foo', 'a');
		self::assertEquals($res['test'], 'test');

		$res = $parser->match('Bar', 'a');
		self::assertEquals($res['test'], 'test');

		$parser = $this->buildParser('
			/*!* BasicInheritanceConstructFallbackParser2
			Foo: "a"
				function __construct(&$res){ $res["testa"] = "testa"; }
			Bar extends Foo
				function __construct(&$res){ $res["testb"] = "testb"; }
			*/
		');

		$res = $parser->match('Foo', 'a');
		self::assertArrayHasKey('testa', $res);
		self::assertEquals($res['testa'], 'testa');
		self::assertArrayNotHasKey('testb', $res);

		$res = $parser->match('Bar', 'a');
		self::assertArrayHasKey('testb', $res);
		self::assertEquals($res['testb'], 'testb');
		self::assertArrayNotHasKey('testa', $res);

	}

	public function testBasicInheritanceStoreFallback() {

		$parser = $this->buildParser('
			/*!* BasicInheritanceStoreFallbackParser
			Foo: Pow:"a"
				function *(&$res, $sub){ $res["test"] = "test"; }
			Bar extends Foo
			*/
		');

		$res = $parser->match('Foo', 'a');
		self::assertEquals($res['test'], 'test');

		$res = $parser->match('Bar', 'a');
		self::assertEquals($res['test'], 'test');

		$parser = $this->buildParser('
			/*!* BasicInheritanceStoreFallbackParser2
			Foo: Pow:"a" Zap:"b"
				function *(&$res, $sub){ $res["testa"] = "testa"; }
			Bar extends Foo
				function *(&$res, $sub){ $res["testb"] = "testb"; }
			Baz extends Foo
				function Zap(&$res, $sub){ $res["testc"] = "testc"; }
			*/
		');

		$res = $parser->match('Foo', 'ab');
		self::assertArrayHasKey('testa', $res);
		self::assertEquals($res['testa'], 'testa');
		self::assertArrayNotHasKey('testb', $res);

		$res = $parser->match('Bar', 'ab');
		self::assertArrayHasKey('testb', $res);
		self::assertEquals($res['testb'], 'testb');
		self::assertArrayNotHasKey('testa', $res);

		$res = $parser->match('Baz', 'ab');
		self::assertArrayHasKey('testa', $res);
		self::assertEquals($res['testa'], 'testa');
		self::assertArrayHasKey('testc', $res);
		self::assertEquals($res['testc'], 'testc');
		self::assertArrayNotHasKey('testb', $res);
	}

	public function testInheritanceByReplacement() {
		$parser = $this->buildParser('
			/*!* InheritanceByReplacementParser
			A: "a"
			B: "b"
			Foo: A B
			Bar extends Foo; B => A
			Baz extends Foo; A => ""
			*/
		');

		$parser->assertMatches('Foo', 'ab');
		$parser->assertMatches('Bar', 'aa');
		$parser->assertMatches('Baz', 'b');
	}

}

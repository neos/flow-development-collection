<?php

declare(strict_types=1);

namespace PhpPeg;

require_once "ParserTestBase.php";

final class ParserSyntaxTest extends ParserTestBase {

	public function testBasicRuleSyntax() {
		$parser = $this->buildParser('
			/*!* BasicRuleSyntax
			Foo: "a" "b"
			Bar: "a"
				"b"
			Baz:
				"a" "b"
			Qux:
				"a"
				"b"
			*/
		');

		$parser->assertMatches('Foo', 'ab');
		$parser->assertMatches('Bar', 'ab');
		$parser->assertMatches('Baz', 'ab');
		$parser->assertMatches('Qux', 'ab');
	}

}

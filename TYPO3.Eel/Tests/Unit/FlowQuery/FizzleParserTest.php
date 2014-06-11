<?php
namespace TYPO3\Eel\Tests\Unit\FlowQuery;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/../../../Resources/Private/PHP/php-peg/tests/ParserTestBase.php');

/**
 * Fizzle parser test
 */
class FizzleParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function filterGroupIsMatched() {
		$parser = new \PhpPeg\ParserTestWrapper($this, 'TYPO3\Eel\FlowQuery\FizzleParser');
		$parser->assertMatches('FilterGroup', 'foo[baz] , asdf');
		$parser->assertDoesntMatch('FilterGroup', 'foo[baz] foo2[baz2]');

		$actual = $parser->match('FilterGroup', 'foo[baz], blah');
		$this->assertSame('foo', $actual['Filters'][0]['PropertyNameFilter']);
		$this->assertSame('blah', $actual['Filters'][1]['PropertyNameFilter']);

		$actual = $parser->match('FilterGroup', 'blah');
		$this->assertSame('blah', $actual['Filters'][0]['PropertyNameFilter']);
	}

	/**
	 * @test
	 */
	public function filterIsMatched() {
		$parser = new \PhpPeg\ParserTestWrapper($this, 'TYPO3\Eel\FlowQuery\FizzleParser');
		$parser->assertDoesntMatch('Filter', '*', 'Universal selector not matched');
		$parser->assertMatches('Filter', 'foo');
		$parser->assertMatches('Filter', 'foo-bar');
		$parser->assertMatches('Filter', 'foo[baz]');
		$parser->assertMatches('Filter', 'foo[baz][bar]');
		$parser->assertMatches('Filter', 'foo[baz]');
		$parser->assertMatches('Filter', '[baz][foo="asdf"]');

		$actual = $parser->match('Filter', 'foo[baz][foo  =  asdf]');
		$this->assertSame('foo', $actual['PropertyNameFilter']);

		$this->assertSame('[baz]', $actual['AttributeFilters'][0]['text']);
		$this->assertSame('baz', $actual['AttributeFilters'][0]['Identifier']);
		$this->assertSame('[foo  =  asdf]', $actual['AttributeFilters'][1]['text']);
		$this->assertSame('foo', $actual['AttributeFilters'][1]['Identifier']);
		$this->assertSame('=', $actual['AttributeFilters'][1]['Operator']);
		$this->assertSame('asdf', $actual['AttributeFilters'][1]['Operand']);

		$actual = $parser->match('Filter', '[baz]');
		$this->assertSame('baz', $actual['AttributeFilters'][0]['Identifier']);

		$parser->assertDoesntMatch('Filter', '*');
	}

	/**
	 * @test
	 */
	public function propertyNameFilterIsMatched() {
		$parser = new \PhpPeg\ParserTestWrapper($this, 'TYPO3\Eel\FlowQuery\FizzleParser');
		$parser->assertDoesntMatch('PropertyNameFilter', '\TYPO3\Foo', 'A class name can be used as type selector');
		$parser->assertDoesntMatch('PropertyNameFilter', 'TYPO3.Foo:Bar', 'A TS Object can be used as type selector');
	}

	/**
	 * @test
	 */
	public function attributeFilterIsMatched() {
		$parser = new \PhpPeg\ParserTestWrapper($this, 'TYPO3\Eel\FlowQuery\FizzleParser');
		$parser->assertMatches('AttributeFilter', '[foo]');
		$parser->assertMatches('AttributeFilter', '[	foo   ]');
		$parser->assertMatches('AttributeFilter', '[foo="Bar"]');
		$parser->assertMatches('AttributeFilter', "[foo='Bar']");
		$parser->assertMatches('AttributeFilter', '[foo^="Bar"]');
		$parser->assertMatches('AttributeFilter', '[foo$="Bar"]');
		$parser->assertMatches('AttributeFilter', '[foo*="Bar"]');
		$parser->assertMatches('AttributeFilter', '[_hideInIndex!=0]');
		$parser->assertMatches('AttributeFilter', '[foo   =   "Bar"   ]');
		$parser->assertMatches('AttributeFilter', '[foo   =   Bar   ]');
		$parser->assertMatches('AttributeFilter', '[foo   =   B\\ar   ]');
		$parser->assertMatches('AttributeFilter', '[foo   =   B:ar   ]');
		$parser->assertDoesntMatch('AttributeFilter', '[foo   =   B\[   ]');
		$parser->assertDoesntMatch('AttributeFilter', '[foo   =   Fo"   ]');
		$parser->assertDoesntMatch('AttributeFilter', '[foo   =   Foo ba   ]');

		$parser->assertMatches('AttributeFilter', '[instanceof "asdf"]');
		$parser->assertMatches('AttributeFilter', '[instanceof asdf]');
		$parser->assertMatches('AttributeFilter', '[foo instanceof string]');
	}

	/**
	 * @test
	 */
	public function booleanOperandsAreConvertedToBoolean() {
		$parser = new \PhpPeg\ParserTestWrapper($this, 'TYPO3\Eel\FlowQuery\FizzleParser');

		$actual = $parser->match('Filter', 'foo[foo=true]');
		$this->assertSame(TRUE, $actual['AttributeFilters'][0]['Operand']);

		$actual = $parser->match('Filter', 'foo[foo= FALSE]');
		$this->assertSame(FALSE, $actual['AttributeFilters'][0]['Operand']);
	}

}

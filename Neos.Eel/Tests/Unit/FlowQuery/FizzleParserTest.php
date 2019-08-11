<?php
namespace Neos\Eel\Tests\Unit\FlowQuery;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use PhpPeg\ParserTestWrapper;
use Neos\Eel\FlowQuery\FizzleParser;

require_once(__DIR__ . '/../../../Resources/Private/PHP/php-peg/tests/ParserTestBase.php');

/**
 * Fizzle parser test
 */
class FizzleParserTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function filterGroupIsMatched()
    {
        $parser = new ParserTestWrapper($this, FizzleParser::class);
        $parser->assertMatches('FilterGroup', 'foo[baz] , asdf');
        $parser->assertDoesntMatch('FilterGroup', 'foo[baz] foo2[baz2]');

        $actual = $parser->match('FilterGroup', 'foo[baz], blah');
        self::assertSame('foo', $actual['Filters'][0]['PropertyNameFilter']);
        self::assertSame('blah', $actual['Filters'][1]['PropertyNameFilter']);

        $actual = $parser->match('FilterGroup', 'blah');
        self::assertSame('blah', $actual['Filters'][0]['PropertyNameFilter']);
    }

    /**
     * @test
     */
    public function filterIsMatched()
    {
        $parser = new ParserTestWrapper($this, FizzleParser::class);
        $parser->assertDoesntMatch('Filter', '*', 'Universal selector not matched');
        $parser->assertMatches('Filter', 'foo');
        $parser->assertMatches('Filter', 'foo-bar');
        $parser->assertMatches('Filter', 'foo[baz]');
        $parser->assertMatches('Filter', 'foo[baz][bar]');
        $parser->assertMatches('Filter', 'foo[baz]');
        $parser->assertMatches('Filter', 'foo[bar.baz]');
        $parser->assertMatches('Filter', '[baz][foo="asdf"]');

        $actual = $parser->match('Filter', 'foo[baz][foo  =  asdf]');
        self::assertSame('foo', $actual['PropertyNameFilter']);

        self::assertSame('[baz]', $actual['AttributeFilters'][0]['text']);
        self::assertSame('baz', $actual['AttributeFilters'][0]['PropertyPath']);
        self::assertSame('[foo  =  asdf]', $actual['AttributeFilters'][1]['text']);
        self::assertSame('foo', $actual['AttributeFilters'][1]['PropertyPath']);
        self::assertSame('=', $actual['AttributeFilters'][1]['Operator']);
        self::assertSame('asdf', $actual['AttributeFilters'][1]['Operand']);

        $actual = $parser->match('Filter', '[baz]');
        self::assertSame('baz', $actual['AttributeFilters'][0]['PropertyPath']);
        self::assertSame('baz', $actual['AttributeFilters'][0]['Identifier'], 'Identifier key is added for compatibility');

        $parser->assertDoesntMatch('Filter', '*');
    }

    /**
     * @test
     */
    public function propertyNameFilterIsMatched()
    {
        $parser = new ParserTestWrapper($this, FizzleParser::class);
        $parser->assertDoesntMatch('PropertyNameFilter', '\Neos\Foo', 'A class name can be used as type selector');
        $parser->assertDoesntMatch('PropertyNameFilter', 'Neos\Foo', 'A class name can be used as type selector');
        $parser->assertDoesntMatch('PropertyNameFilter', 'Neos.Foo:Bar', 'A TS Object can be used as type selector');
    }

    /**
     * @test
     */
    public function pathFilterIsMatched()
    {
        $parser = new ParserTestWrapper($this, FizzleParser::class);
        $parser->assertMatches('Filter', '/sites/foo');
        $parser->assertMatches('Filter', 'foo/bar');
        $parser->assertMatches('Filter', 'foo/node_1234-5678');
        $parser->assertMatches('Filter', '/');
        $parser->assertDoesntMatch('Filter', 'foo/');
        $parser->assertDoesntMatch('Filter', '/foo/');
        $parser->assertDoesntMatch('Filter', 'foo//bar');
        $parser->assertDoesntMatch('Filter', 'foo/bar?');
        $parser->assertDoesntMatch('Filter', '*foo/bar');
        $parser->assertDoesntMatch('PathFilter', 'foo');

        $actual = $parser->match('Filter', 'foo/bar');
        self::assertSame('foo/bar', $actual['PathFilter']);
    }

    /**
     * @test
     */
    public function attributeFilterIsMatched()
    {
        $parser = new ParserTestWrapper($this, FizzleParser::class);
        $parser->assertMatches('AttributeFilter', '[foo]');
        $parser->assertMatches('AttributeFilter', '[	foo   ]');
        $parser->assertMatches('AttributeFilter', '[foo="Bar"]');
        $parser->assertMatches('AttributeFilter', "[foo='Bar']");
        $parser->assertMatches('AttributeFilter', '[foo^="Bar"]');
        $parser->assertMatches('AttributeFilter', '[foo$="Bar"]');
        $parser->assertMatches('AttributeFilter', '[foo*="Bar"]');
        $parser->assertMatches('AttributeFilter', '[_hideInIndex!=0]');
        $parser->assertMatches('AttributeFilter', '[foo<0]');
        $parser->assertMatches('AttributeFilter', '[foo<=0]');
        $parser->assertMatches('AttributeFilter', '[foo>0]');
        $parser->assertMatches('AttributeFilter', '[foo>=0]');
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
    public function booleanOperandsAreConvertedToBoolean()
    {
        $parser = new ParserTestWrapper($this, FizzleParser::class);

        $actual = $parser->match('Filter', 'foo[foo=true]');
        self::assertSame(true, $actual['AttributeFilters'][0]['Operand']);

        $actual = $parser->match('Filter', 'foo[foo= false]');
        self::assertSame(false, $actual['AttributeFilters'][0]['Operand']);
    }
}

<?php
namespace TYPO3\Flow\Tests\Unit\Object\Proxy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../Fixture/FooBarAnnotation.php');

use TYPO3\Flow\Annotations\Inject;
use TYPO3\Flow\Annotations\Scope;
use TYPO3\Flow\Annotations\Session;
use TYPO3\Flow\Annotations\Signal;
use TYPO3\Flow\Annotations\Validate;
use TYPO3\Flow\Object\Proxy\Compiler;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test cases for the Proxy Compiler
 */
class CompilerTest extends UnitTestCase
{
    /**
     * @var Compiler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $compiler;

    protected function setUp()
    {
        $this->compiler = $this->getAccessibleMock(Compiler::class, null);
    }

    /**
     * @return array
     */
    public function annotationsAndStrings()
    {
        $sessionWithAutoStart = new Session();
        $sessionWithAutoStart->autoStart = true;
        return [
            [
                new Signal([]),
                '@\TYPO3\Flow\Annotations\Signal'
            ],
            [
                new Scope(['value' => 'singleton']),
                '@\TYPO3\Flow\Annotations\Scope("singleton")'
            ],
            [
                new FooBarAnnotation(),
                '@\TYPO3\Flow\Tests\Unit\Object\Proxy\FooBarAnnotation(1.2)'
            ],
            [
                new FooBarAnnotation(new FooBarAnnotation()),
                '@\TYPO3\Flow\Tests\Unit\Object\Proxy\FooBarAnnotation(@\TYPO3\Flow\Tests\Unit\Object\Proxy\FooBarAnnotation(1.2))'
            ],
            [
                $sessionWithAutoStart,
                '@\TYPO3\Flow\Annotations\Session(autoStart=true)'
            ],
            [
                new Session(),
                '@\TYPO3\Flow\Annotations\Session'
            ],
            [
                new Validate(['value' => 'foo1', 'type' => 'bar1']),
                '@\TYPO3\Flow\Annotations\Validate(type="bar1", argumentName="foo1")'
            ],
            [
                new Validate(['type' => 'bar1', 'options' => ['minimum' => 2]]),
                '@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "minimum"=2 })'
            ],
            [
                new Validate(['type' => 'bar1', 'options' => ['foo' => ['bar' => 'baz']]]),
                '@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "foo"={ "bar"="baz" } })'
            ],
            [
                new Validate(['type' => 'bar1', 'options' => ['foo' => 'hubbabubba', 'bar' => true]]),
                '@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba", "bar"=true })'
            ],
            [
                new Validate(['type' => 'bar1', 'options' => [new Inject([])]]),
                '@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ @\TYPO3\Flow\Annotations\Inject })'
            ],
            [
                new Validate(['type' => 'bar1', 'options' => [new Validate(['type' => 'bar1', 'options' => ['foo' => 'hubbabubba']])]]),
                '@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ @\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba" }) })'
            ],
        ];
    }

    /**
     * @dataProvider annotationsAndStrings
     * @test
     */
    public function renderAnnotationRendersCorrectly($annotation, $expectedString)
    {
        $this->assertEquals($expectedString, Compiler::renderAnnotation($annotation));
    }

    /**
     * @return array
     */
    public function stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider()
    {
        return [
                // no (valid) php file
            ['classCode' => "", 'expectedResult' => ""],
            ['classCode' => "Not\nPHP code\n", 'expectedResult' => "Not\nPHP code\n"],

                // PHP files with only one line
            ['classCode' => "<?php just one line", 'expectedResult' => " just one line"],
            ['classCode' => "<?php another <?php tag", 'expectedResult' => " another <?php tag"],
            ['classCode' => "  <?php  space before and after tag", 'expectedResult' => "  space before and after tag"],

                // PHP files with more lines
            ['classCode' => "<?php\nsecond line", 'expectedResult' => "\nsecond line"],
            ['classCode' => "  <?php\nsecond line", 'expectedResult' => "\nsecond line"],
            ['classCode' => "<?php  first line\nsecond line", 'expectedResult' => "  first line\nsecond line"],
            ['classCode' => "<?php\nsecond line with another <?php tag", 'expectedResult' => "\nsecond line with another <?php tag"],
            ['classCode' => "\n<?php\nempty line before php tag", 'expectedResult' => "\nempty line before php tag"],
            ['classCode' => "<?php\nsecond line\n<?php\nthird line", 'expectedResult' => "\nsecond line\n<?php\nthird line"],
        ];
    }

    /**
     * @param string $classCode
     * @param string $expectedResult
     * @test
     * @dataProvider stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider
     */
    public function stripOpeningPhpTagCorrectlyStripsPhpTagTests($classCode, $expectedResult)
    {
        $actualResult = $this->compiler->_call('stripOpeningPhpTag', $classCode);
        $this->assertSame($expectedResult, $actualResult);
    }
}

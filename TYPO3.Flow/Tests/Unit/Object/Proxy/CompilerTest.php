<?php
namespace TYPO3\Flow\Tests\Unit\Object\Proxy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
class CompilerTest extends UnitTestCase {

	/**
	 * @var Compiler|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $compiler;

	protected function setUp() {
		$this->compiler = $this->getAccessibleMock('TYPO3\Flow\Object\Proxy\Compiler', NULL);
	}

	/**
	 * @return array
	 */
	public function annotationsAndStrings() {
		$sessionWithAutoStart = new Session();
		$sessionWithAutoStart->autoStart = TRUE;
		return array(
			array(
				new Signal(array()),
				'@\TYPO3\Flow\Annotations\Signal'
			),
			array(
				new Scope(array('value' => 'singleton')),
				'@\TYPO3\Flow\Annotations\Scope("singleton")'
			),
			array(
				new FooBarAnnotation(),
				'@\TYPO3\Flow\Tests\Unit\Object\Proxy\FooBarAnnotation(1.2)'
			),
			array(
				new FooBarAnnotation(new FooBarAnnotation()),
				'@\TYPO3\Flow\Tests\Unit\Object\Proxy\FooBarAnnotation(@\TYPO3\Flow\Tests\Unit\Object\Proxy\FooBarAnnotation(1.2))'
			),
			array(
				$sessionWithAutoStart,
				'@\TYPO3\Flow\Annotations\Session(autoStart=true)'
			),
			array(
				new Session(),
				'@\TYPO3\Flow\Annotations\Session'
			),
			array(
				new Validate(array('value' => 'foo1', 'type' => 'bar1')),
				'@\TYPO3\Flow\Annotations\Validate(type="bar1", argumentName="foo1")'
			),
			array(
				new Validate(array('type' => 'bar1', 'options' => array('minimum' => 2))),
				'@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "minimum"=2 })'
			),
			array(
				new Validate(array('type' => 'bar1', 'options' => array('foo' => array('bar' => 'baz')))),
				'@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "foo"={ "bar"="baz" } })'
			),
			array(
				new Validate(array('type' => 'bar1', 'options' => array('foo' => 'hubbabubba', 'bar' => TRUE))),
				'@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba", "bar"=true })'
			),
			array(
				new Validate(array('type' => 'bar1', 'options' => array(new Inject(array())))),
				'@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ @\TYPO3\Flow\Annotations\Inject })'
			),
			array(
				new Validate(array('type' => 'bar1', 'options' => array(new Validate(array('type' => 'bar1', 'options' => array('foo' => 'hubbabubba')))))),
				'@\TYPO3\Flow\Annotations\Validate(type="bar1", options={ @\TYPO3\Flow\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba" }) })'
			),
		);
	}

	/**
	 * @dataProvider annotationsAndStrings
	 * @test
	 */
	public function renderAnnotationRendersCorrectly($annotation, $expectedString) {
		$this->assertEquals($expectedString, Compiler::renderAnnotation($annotation));
	}

	/**
	 * @return array
	 */
	public function stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider() {
		return array(
				// no (valid) php file
			array('classCode' => "", 'expectedResult' => ""),
			array('classCode' => "Not\nPHP code\n", 'expectedResult' => "Not\nPHP code\n"),

				// PHP files with only one line
			array('classCode' => "<?php just one line", 'expectedResult' => " just one line"),
			array('classCode' => "<?php another <?php tag", 'expectedResult' => " another <?php tag"),
			array('classCode' => "  <?php  space before and after tag", 'expectedResult' => "  space before and after tag"),

				// PHP files with more lines
			array('classCode' => "<?php\nsecond line", 'expectedResult' => "\nsecond line"),
			array('classCode' => "  <?php\nsecond line", 'expectedResult' => "\nsecond line"),
			array('classCode' => "<?php  first line\nsecond line", 'expectedResult' => "  first line\nsecond line"),
			array('classCode' => "<?php\nsecond line with another <?php tag", 'expectedResult' => "\nsecond line with another <?php tag"),
			array('classCode' => "\n<?php\nempty line before php tag", 'expectedResult' => "\nempty line before php tag"),
			array('classCode' => "<?php\nsecond line\n<?php\nthird line", 'expectedResult' => "\nsecond line\n<?php\nthird line"),
		);
	}

	/**
	 * @param string $classCode
	 * @param string $expectedResult
	 * @test
	 * @dataProvider stripOpeningPhpTagCorrectlyStripsPhpTagDataProvider
	 */
	public function stripOpeningPhpTagCorrectlyStripsPhpTagTests($classCode, $expectedResult) {
		$actualResult = $this->compiler->_call('stripOpeningPhpTag', $classCode);
		$this->assertSame($expectedResult, $actualResult);
	}

}

/**
 * fixture "annotation" for the above test case
 */
class FooBarAnnotation {
	public $value;
	public function __construct($value = 1.2) {
		$this->value = $value;
	}
}

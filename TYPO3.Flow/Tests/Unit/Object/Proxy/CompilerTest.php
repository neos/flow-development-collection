<?php
namespace TYPO3\FLOW3\Tests\Unit\Object\Proxy;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 *
 */
class CompilerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @return array
	 */
	public function annotationsAndStrings() {
		$sessionWithAutoStart = new \TYPO3\FLOW3\Annotations\Session();
		$sessionWithAutoStart->autoStart = TRUE;
		return array(
			array(
				new \TYPO3\FLOW3\Annotations\Signal(array()),
				'@\TYPO3\FLOW3\Annotations\Signal'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Scope(array('value' => 'singleton')),
				'@\TYPO3\FLOW3\Annotations\Scope("singleton")'
			),
			array(
				new FooBarAnnotation(),
				'@\TYPO3\FLOW3\Tests\Unit\Object\Proxy\FooBarAnnotation(1.2)'
			),
			array(
				new FooBarAnnotation(new FooBarAnnotation()),
				'@\TYPO3\FLOW3\Tests\Unit\Object\Proxy\FooBarAnnotation(@\TYPO3\FLOW3\Tests\Unit\Object\Proxy\FooBarAnnotation(1.2))'
			),
			array(
				$sessionWithAutoStart,
				'@\TYPO3\FLOW3\Annotations\Session(autoStart=true)'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Session(),
				'@\TYPO3\FLOW3\Annotations\Session'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Validate(array('value' => 'foo1', 'type' => 'bar1')),
				'@\TYPO3\FLOW3\Annotations\Validate(type="bar1", argumentName="foo1")'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Validate(array('type' => 'bar1', 'options' => array('minimum' => 2))),
				'@\TYPO3\FLOW3\Annotations\Validate(type="bar1", options={ "minimum"=2 })'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Validate(array('type' => 'bar1', 'options' => array('foo' => array('bar' => 'baz')))),
				'@\TYPO3\FLOW3\Annotations\Validate(type="bar1", options={ "foo"={ "bar"="baz" } })'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Validate(array('type' => 'bar1', 'options' => array('foo' => 'hubbabubba', 'bar' => TRUE))),
				'@\TYPO3\FLOW3\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba", "bar"=true })'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Validate(array('type' => 'bar1', 'options' => array(new \TYPO3\FLOW3\Annotations\Inject(array())))),
				'@\TYPO3\FLOW3\Annotations\Validate(type="bar1", options={ @\TYPO3\FLOW3\Annotations\Inject })'
			),
			array(
				new \TYPO3\FLOW3\Annotations\Validate(array('type' => 'bar1', 'options' => array(new \TYPO3\FLOW3\Annotations\Validate(array('type' => 'bar1', 'options' => array('foo' => 'hubbabubba')))))),
				'@\TYPO3\FLOW3\Annotations\Validate(type="bar1", options={ @\TYPO3\FLOW3\Annotations\Validate(type="bar1", options={ "foo"="hubbabubba" }) })'
			),
		);
	}

	/**
	 * @dataProvider annotationsAndStrings
	 * @test
	 */
	public function renderAnnotationRendersCorrectly($annotation, $expectedString) {
		$this->assertEquals($expectedString, \TYPO3\FLOW3\Object\Proxy\Compiler::renderAnnotation($annotation));
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

?>

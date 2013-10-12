<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Context;

/**
 * Eel context test
 */
class ContextTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data provider with simple values
	 *
	 * @return array
	 */
	public function simpleValues() {
		return array(
			array('Test', 'Test'),
			array(TRUE, TRUE),
			array(42, 42),
			array(7.0, 7.0),
			array(NULL, NULL)
		);
	}

	/**
	 * @test
	 * @dataProvider simpleValues
	 *
	 * @param mixed $value
	 * @param mixed $expectedUnwrappedValue
	 */
	public function unwrapSimpleValues($value, $expectedUnwrappedValue) {
		$context = new Context($value);
		$unwrappedValue = $context->unwrap();
		$this->assertSame($expectedUnwrappedValue, $unwrappedValue);
	}

	/**
	 * Data provider with array values
	 *
	 * @return array
	 */
	public function arrayValues() {
		return array(
			array(array(), array()),
			array(array(1, 2, 3), array(1, 2, 3)),
			// Unwrap has to be recursive
			array(array(new Context('Foo')), array('Foo')),
			array(array('arr' => array(new Context('Foo'))), array('arr' => array('Foo')))
		);
	}

	/**
	 * @test
	 * @dataProvider arrayValues
	 *
	 * @param mixed $value
	 * @param mixed $expectedUnwrappedValue
	 */
	public function unwrapArrayValues($value, $expectedUnwrappedValue) {
		$context = new Context($value);
		$unwrappedValue = $context->unwrap();
		$this->assertSame($expectedUnwrappedValue, $unwrappedValue);
	}

	/**
	 * Data provider with array values
	 *
	 * @return array
	 */
	public function arrayGetValues() {
		return array(
			array(array(), 'foo', NULL),
			array(array('foo' => 'bar'), 'foo', 'bar'),
			array(array(1, 2, 3), '1', 2),
			array(array('foo' => array('bar' => 'baz')), 'foo', array('bar' => 'baz')),
			array(new \ArrayObject(array('foo' => 'bar')), 'foo', 'bar')
		);
	}

	/**
	 * @test
	 * @dataProvider arrayGetValues
	 *
	 * @param mixed $value
	 * @param string $path
	 * @param mixed $expectedGetValue
	 */
	public function getValueByPathForArrayValues($value, $path, $expectedGetValue) {
		$context = new Context($value);
		$getValue = $context->get($path);
		$this->assertSame($getValue, $expectedGetValue);
	}

	/**
	 * Data provider with object values
	 *
	 * @return array
	 */
	public function objectGetValues() {
		$simpleObject = new \stdClass();
		$simpleObject->foo = 'bar';
		$getterObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();
		$getterObject->setProperty('some value');
		$getterObject->setBooleanProperty(TRUE);

		return array(
			array($simpleObject, 'bar', NULL),
			array($simpleObject, 'foo', 'bar'),
			array($getterObject, 'foo', NULL),
			array($getterObject, 'callMe', NULL),
			array($getterObject, 'booleanProperty', TRUE)
		);
	}

	/**
	 * @test
	 * @dataProvider objectGetValues
	 *
	 * @param mixed $value
	 * @param string $path
	 * @param mixed $expectedGetValue
	 */
	public function getValueByPathForObjectValues($value, $path, $expectedGetValue) {
		$context = new Context($value);
		$getValue = $context->get($path);
		$this->assertSame($getValue, $expectedGetValue);
	}

}

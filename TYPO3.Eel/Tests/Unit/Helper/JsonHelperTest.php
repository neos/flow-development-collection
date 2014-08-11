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

use TYPO3\Eel\Helper\JsonHelper;

/**
 * Tests for JsonHelper
 */
class JsonHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	public function stringifyExamples() {
		return array(
			'string value' => array(
				'Foo', '"Foo"'
			),
			'null value' => array(
				NULL, 'null'
			),
			'numeric value' => array(
				42, '42'
			),
			'array value' => array(
				array('Foo', 'Bar'), '["Foo","Bar"]'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider stringifyExamples
	 */
	public function stringifyWorks($value, $expected) {
		$helper = new JsonHelper();
		$result = $helper->stringify($value);
		$this->assertEquals($expected, $result);
	}

	public function parseExamples() {
		return array(
			'string value' => array(
				array('"Foo"'), 'Foo'
			),
			'null value' => array(
				array('null'), NULL
			),
			'numeric value' => array(
				array('42'), 42
			),
			'array value' => array(
				array('["Foo","Bar"]'), array('Foo', 'Bar')
			),
			'object value is parsed as associative array by default' => array(
				array('{"name":"Foo"}'), array('name' => 'Foo')
			),
			'object value without associative array' => array(
				array('{"name":"Foo"}', FALSE), (object)array('name' => 'Foo')
			)
		);
	}

	/**
	 * @test
	 * @dataProvider parseExamples
	 */
	public function parseWorks($arguments, $expected) {
		$helper = new JsonHelper();
		$result = call_user_func_array(array($helper, 'parse'), $arguments);
		$this->assertEquals($expected, $result);
	}

}

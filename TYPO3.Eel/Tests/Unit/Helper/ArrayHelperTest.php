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

/**
 * Test for ArrayHelper
 */
class ArrayHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	public function concatExamples() {
		return array(
			'alpha and numeric values' => array(
				array(array('a', 'b', 'c'), array(1, 2, 3)),
				array('a', 'b', 'c', 1, 2, 3)
			),
			'variable arguments' =>  array(
				array(array('a', 'b', 'c'), array(1, 2, 3), array(4, 5, 6)),
				array('a', 'b', 'c', 1, 2, 3, 4, 5, 6)
			),
			'mixed arguments' =>  array(
				array(array('a', 'b', 'c'), 1, array(2, 3)),
				array('a', 'b', 'c', 1, 2, 3)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider concatExamples
	 */
	public function concatWorks($arguments, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = call_user_func_array(array($helper, 'concat'), $arguments);
		$this->assertEquals($expected, $result);
	}

	public function joinExamples() {
		return array(
			'words with default separator' => array(array('a', 'b', 'c'), NULL, 'a,b,c'),
			'words with custom separator' => array(array('a', 'b', 'c'), ', ', 'a, b, c'),
			'empty array' => array(array(), ', ', ''),
		);
	}

	/**
	 * @test
	 * @dataProvider joinExamples
	 */
	public function joinWorks($array, $separator, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		if ($separator !== NULL) {
			$result = $helper->join($array, $separator);
		} else {
			$result = $helper->join($array);
		}
		$this->assertEquals($expected, $result);
	}

	public function sliceExamples() {
		return array(
			'positive begin without end' => array(array('a', 'b', 'c', 'd', 'e'), 2, NULL, array('c', 'd', 'e')),
			'negative begin without end' => array(array('a', 'b', 'c', 'd', 'e'), -2, NULL, array('d', 'e')),
			'positive begin and end' => array(array('a', 'b', 'c', 'd', 'e'), 1, 3, array('b', 'c')),
			'positive begin with negative end' => array(array('a', 'b', 'c', 'd', 'e'), 1, -2, array('b', 'c')),
			'empty array' => array(array(), 1, -2, array()),
		);
	}

	/**
	 * @test
	 * @dataProvider sliceExamples
	 */
	public function sliceWorks($array, $begin, $end, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		if ($end !== NULL) {
			$result = $helper->slice($array, $begin, $end);
		} else {
			$result = $helper->slice($array, $begin);
		}
		$this->assertEquals($expected, $result);
	}

	public function reverseExamples() {
		return array(
			'empty array' => array(array(), array()),
			'numeric indices' => array(array('a', 'b', 'c'), array('c', 'b', 'a')),
			'string keys' => array(array('foo' => 'bar', 'bar' => 'baz'), array('bar' => 'baz', 'foo' => 'bar')),
		);
	}

	/**
	 * @test
	 * @dataProvider reverseExamples
	 */
	public function reverseWorks($array, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = $helper->reverse($array);

		$this->assertEquals($expected, $result);
	}

	public function keysExamples() {
		return array(
			'empty array' => array(array(), array()),
			'numeric indices' => array(array('a', 'b', 'c'), array(0, 1, 2)),
			'string keys' => array(array('foo' => 'bar', 'bar' => 'baz'), array('foo', 'bar')),
		);
	}

	/**
	 * @test
	 * @dataProvider keysExamples
	 */
	public function keysWorks($array, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = $helper->keys($array);

		$this->assertEquals($expected, $result);
	}

	public function lengthExamples() {
		return array(
			'empty array' => array(array(), 0),
			'array with values' => array(array('a', 'b', 'c'), 3)
		);
	}

	/**
	 * @test
	 * @dataProvider lengthExamples
	 */
	public function lengthWorks($array, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = $helper->length($array);

		$this->assertEquals($expected, $result);
	}

	public function indexOfExamples() {
		return array(
			'empty array' => array(array(), 42, NULL, -1),
			'array with values' => array(array('a', 'b', 'c', 'b'), 'b', NULL, 1),
			'with offset' => array(array('a', 'b', 'c', 'b'), 'b', 2, 3)
		);
	}

	/**
	 * @test
	 * @dataProvider indexOfExamples
	 */
	public function indexOfWorks($array, $searchElement, $fromIndex, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		if ($fromIndex !== NULL) {
			$result = $helper->indexOf($array, $searchElement, $fromIndex);
		} else {
			$result = $helper->indexOf($array, $searchElement);
		}

		$this->assertEquals($expected, $result);
	}

	public function isEmptyExamples() {
		return array(
			'empty array' => array(array(), TRUE),
			'array with values' => array(array('a', 'b', 'c'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider isEmptyExamples
	 */
	public function isEmptyWorks($array, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = $helper->isEmpty($array);

		$this->assertEquals($expected, $result);
	}

	public function firstExamples() {
		return array(
			'empty array' => array(array(), FALSE),
			'numeric indices' => array(array('a', 'b', 'c'), 'a'),
			'string keys' => array(array('foo' => 'bar', 'bar' => 'baz'), 'bar'),
		);
	}

	/**
	 * @test
	 * @dataProvider firstExamples
	 */
	public function firstWorks($array, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = $helper->first($array);

		$this->assertEquals($expected, $result);
	}

	public function lastExamples() {
		return array(
			'empty array' => array(array(), FALSE),
			'numeric indices' => array(array('a', 'b', 'c'), 'c'),
			'string keys' => array(array('foo' => 'bar', 'bar' => 'baz'), 'baz'),
		);
	}

	/**
	 * @test
	 * @dataProvider lastExamples
	 */
	public function lastWorks($array, $expected) {
		$helper = new \TYPO3\Eel\Helper\ArrayHelper();
		$result = $helper->last($array);

		$this->assertEquals($expected, $result);
	}
}

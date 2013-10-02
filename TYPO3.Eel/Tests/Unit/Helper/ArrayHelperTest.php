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

use TYPO3\Eel\Helper\ArrayHelper;

/**
 * Tests for ArrayHelper
 */
class ArrayHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	public function concatExamples() {
		return array(
			'alpha and numeric values' => array(
				array(array('a', 'b', 'c'), array(1, 2, 3)),
				array('a', 'b', 'c', 1, 2, 3)
			),
			'variable arguments' => array(
				array(array('a', 'b', 'c'), array(1, 2, 3), array(4, 5, 6)),
				array('a', 'b', 'c', 1, 2, 3, 4, 5, 6)
			),
			'mixed arguments' => array(
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
			'zero begin with negative end' => array(array('a', 'b', 'c', 'd', 'e'), 0, -1, array('a', 'b', 'c', 'd')),
			'empty array' => array(array(), 1, -2, array()),
		);
	}

	/**
	 * @test
	 * @dataProvider sliceExamples
	 */
	public function sliceWorks($array, $begin, $end, $expected) {
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
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
		$helper = new ArrayHelper();
		$result = $helper->last($array);

		$this->assertEquals($expected, $result);
	}

	public function randomExamples() {
		return array(
			'empty array' => array(array(), FALSE),
			'numeric indices' => array(array('a', 'b', 'c'), TRUE),
			'string keys' => array(array('foo' => 'bar', 'bar' => 'baz'), TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider randomExamples
	 */
	public function randomWorks($array, $expected) {
		$helper = new ArrayHelper();
		$result = $helper->random($array);

		$this->assertEquals($expected, in_array($result, $array));
	}

	public function sortExamples() {
		return array(
			'empty array' => array(array(), array()),
			'numeric indices' => array(array('z', '7d', 'i', '7', 'm', 8, 3, 'q'), array(3, '7', '7d', 8, 'i', 'm', 'q', 'z')),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'), array('foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo')),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53), array('k' => 53, 76, '84216', 'bar', 'foo', 'i' => 181.84, 'foo' => 'abc')),
		);
	}

	/**
	 * @test
	 * @dataProvider sortExamples
	 */
	public function sortWorks($array, $expected) {
		$helper = new ArrayHelper();
		$sortedArray = $helper->sort($array);
		$this->assertEquals($expected, $sortedArray);
	}

	public function shuffleExamples() {
		return array(
			'empty array' => array(array()),
			'numeric indices' => array(array('z', '7d', 'i', '7', 'm', 8, 3, 'q')),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz')),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53)),
		);
	}

	/**
	 * @test
	 * @dataProvider shuffleExamples
	 */
	public function shuffleWorks($array) {
		$helper = new ArrayHelper();
		$shuffledArray = $helper->shuffle($array);
		$this->assertEquals($array, $shuffledArray);
	}

	public function popExamples() {
		return array(
			'empty array' => array(array(), array()),
			'numeric indices' => array(array('z', '7d', 'i', '7'), array('z', '7d', 'i')),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'), array('foo' => 'bar', 'baz' => 'foo')),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53), array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76)),
		);
	}

	/**
	 * @test
	 * @dataProvider popExamples
	 */
	public function popWorks($array, $expected) {
		$helper = new ArrayHelper();
		$poppedArray = $helper->pop($array);
		$this->assertEquals($expected, $poppedArray);
	}

	public function pushExamples() {
		return array(
			'empty array' => array(array(), 42, 'foo', array(42, 'foo')),
			'numeric indices' => array(array('z', '7d', 'i', '7'), 42, 'foo', array('z', '7d', 'i', '7', 42, 'foo')),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'), 42, 'foo', array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz', 42, 'foo')),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53), 42, 'foo', array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53, 42, 'foo')),
		);
	}

	/**
	 * @test
	 * @dataProvider pushExamples
	 */
	public function pushWorks($array, $element1, $element2, $expected) {
		$helper = new ArrayHelper();
		$pushedArray = $helper->push($array, $element1, $element2);
		$this->assertEquals($expected, $pushedArray);
	}

	public function shiftExamples() {
		return array(
			'empty array' => array(array(), array()),
			'numeric indices' => array(array('z', '7d', 'i', '7'), array('7d', 'i', '7')),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'), array('baz' => 'foo', 'bar' => 'baz')),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53), array('foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53)),
		);
	}

	/**
	 * @test
	 * @dataProvider shiftExamples
	 */
	public function shiftWorks($array, $expected) {
		$helper = new ArrayHelper();
		$shiftedArray = $helper->shift($array);
		$this->assertEquals($expected, $shiftedArray);
	}

	public function unshiftExamples() {
		return array(
			'empty array' => array(array(), 'abc', 42, array(42, 'abc')),
			'numeric indices' => array(array('z', '7d', 'i', '7'), 'abc', 42, array(42, 'abc', 'z', '7d', 'i', '7')),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'), 'abc', 42, array(42, 'abc', 'foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz')),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53), 'abc', 42, array(42, 'abc', 'bar', 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53)),
		);
	}

	/**
	 * @test
	 * @dataProvider unshiftExamples
	 */
	public function unshiftWorks($array, $element1, $element2, $expected) {
		$helper = new ArrayHelper();
		$unshiftedArray = $helper->unshift($array, $element1, $element2);
		$this->assertEquals($expected, $unshiftedArray);
	}

	public function spliceExamples() {
		return array(
			'empty array' => array(array(), array(42, 'abc', 'TYPO3'), 2, 2, 42, 'abc', 'TYPO3'),
			'numeric indices' => array(array('z', '7d', 'i', '7'), array('z', '7d', 42, 'abc', 'TYPO3'), 2, 2, 42, 'abc', 'TYPO3'),
			'string keys' => array(array('foo' => 'bar', 'baz' => 'foo', 'bar' => 'baz'), array('foo' => 'bar', 'baz' => 'foo', 42, 'abc', 'TYPO3'), 2, 2, 42, 'abc', 'TYPO3'),
			'mixed keys' => array(array('bar', '24' => 'foo', 'i' => 181.84, 'foo' => 'abc', '84216', 76, 'k' => 53), array('bar', 'foo', 42, 'abc', 'TYPO3', '84216', 76, 'k' => 53), 2, 2, 42, 'abc', 'TYPO3'),
		);
	}

	/**
	 * @test
	 * @dataProvider spliceExamples
	 */
	public function spliceWorks($array, $expected, $offset, $length, $element1, $element2, $element3) {
		$helper = new ArrayHelper();
		$splicedArray = $helper->splice($array, $offset, $length, $element1, $element2, $element3);
		$this->assertEquals($expected, $splicedArray);
	}

	/**
	 * @test
	 */
	public function spliceNoReplacements() {
		$helper = new ArrayHelper();
		$splicedArray = $helper->splice(array(0, 1, 2, 3, 4, 5), 2, 2);
		$this->assertEquals(array(0, 1, 4, 5), $splicedArray);
	}

}

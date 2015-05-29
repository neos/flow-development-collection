<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 */
class PositionalArraySorterTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function toArraySortsNumericKeysIfNoPositionMetaDataIsSet() {
		$array = array(2 => 'foo', 1 => 'bar', 'z' => 'baz', 'a' => 'quux');
		$expectedResult = array('z' => 'baz', 'a' => 'quux', 1 => 'bar', 2 => 'foo');

		$positionalArraySorter = new PositionalArraySorter($array);
		$sortedArray = $positionalArraySorter->toArray();
		$this->assertSame($expectedResult, $sortedArray);
	}

	/**
	 * @return array
	 */
	public function invalidPositions() {
		return array(
			array('subject' => array('foo' => array('position' => 'invalid'), 'first' => array())),
			array('subject' => array('foo' => array('position' => 'start123'), 'first' => array())),
			array('subject' => array('foo' => array('position' => 'start 12 34'), 'first' => array())),
			array('subject' => array('foo' => array('position' => 'after 12 34 56'), 'first' => array())),
			array('subject' => array('foo' => array('position' => 'after foo-bar'), 'first' => array())),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidPositions
	 *
	 * @param array $subject
	 * @expectedException \TYPO3\Flow\Utility\Exception\InvalidPositionException
	 */
	public function toArrayThrowsExceptionForInvalidPositions(array $subject) {
		$positionalArraySorter = new PositionalArraySorter($subject);
		$sortedArray = $positionalArraySorter->toArray();
	}

	/**
	 * @return array
	 */
	public function sampleArrays() {
		return array(
			array(
				'message' => 'Position end should put element to end',
				'subject' => array('second' => array('__meta' => array('position' => 'end')), 'first' => array()),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second')
			),
			array(
				'message' => 'Position start should put element to start',
				'subject' => array('second' => array(), 'first' => array('__meta' => array('position' => 'start'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second')
			),
			array(
				'message' => 'Position start should respect priority',
				'subject' => array('second' => array('__meta' => array('position' => 'start 50')), 'first' => array('__meta' => array('position' => 'start 52'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second')
			),
			array(
				'message' => 'Position end should respect priority',
				'subject' => array('second' => array('__meta' => array('position' => 'end 17')), 'first' => array('__meta' => array('position' => 'end'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second')
			),
			array(
				'Positional numbers are in the middle',
				'subject' => array('last' => array('__meta' => array('position' => 'end')), 'second' => array('__meta' => array('position' => '17')), 'first' => array('__meta' => array('position' => '5')), 'third' => array('__meta' => array('position' => '18'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second', 'third', 'last')
			),
			array(
				'message' => 'Position before adds before named element if present',
				'subject' => array('second' => array(), 'first' => array('__meta' => array('position' => 'before second'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second')
			),
			array(
				'message' => 'Position before adds after start if named element not present',
				'subject' => array('third' => array(), 'second' => array('__meta' => array('position' => 'before third')), 'first' => array('__meta' => array('position' => 'before unknown'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second', 'third')
			),
			array(
				'message' => 'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.',
				'subject' => array('third' => array(), 'second' => array('__meta' => array('position' => 'before third')), 'first' => array('__meta' => array('position' => 'before third 12'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('second', 'first', 'third')
			),
			array(
				'message' => 'Position before works recursively',
				'subject' => array('third' => array(), 'second' => array('__meta' => array('position' => 'before third')), 'first' => array('__meta' => array('position' => 'before second'))),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second', 'third')
			),
			array(
				'Position after adds after named element if present',
				'subject' => array('second' => array('__meta' => array('position' => 'after first')), 'first' => array()),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second')
			),
			array(
				'message' => 'Position after adds before end if named element not present',
				'subject' => array('second' => array('__meta' => array('position' => 'after unknown')), 'third' => array('__meta' => array('position' => 'end')), 'first' => array()),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second', 'third')
			),
			array(
				'message' => 'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.',
				'subject' => array('third' => array('__meta' => array('position' => 'after first')), 'second' => array('__meta' => array('position' => 'after first 12')), 'first' => array()),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second', 'third')
			),
			array(
				'message' => 'Position after works recursively',
				'subject' => array('third' => array('__meta' => array('position' => 'after second')), 'second' => array('__meta' => array('position' => 'after first')), 'first' => array()),
				'positionPropertyPath' => '__meta.position',
				'expectedArrayKeys' => array('first', 'second', 'third')
			)
		);
	}

	/**
	 * @test
	 * @dataProvider sampleArrays
	 *
	 * @param string $message
	 * @param array $subject
	 * @param string $positionPropertyPath
	 * @param array $expectedKeyOrder
	 */
	public function toArrayTests($message, array $subject, $positionPropertyPath, array $expectedKeyOrder) {
		$positionalArraySorter = new PositionalArraySorter($subject, $positionPropertyPath);
		$result = $positionalArraySorter->toArray();

		$this->assertSame($expectedKeyOrder, array_keys($result), $message);
	}

	/**
	 * @test
	 * @dataProvider sampleArrays
	 *
	 * @param string $message
	 * @param array $subject
	 * @param string $positionPropertyPath
	 * @param array $expectedKeyOrder
	 */
	public function getSortedKeysTests($message, array $subject, $positionPropertyPath, array $expectedKeyOrder) {
		$positionalArraySorter = new PositionalArraySorter($subject, $positionPropertyPath);
		$result = $positionalArraySorter->getSortedKeys();

		$this->assertSame($expectedKeyOrder, $result, $message);
	}
}

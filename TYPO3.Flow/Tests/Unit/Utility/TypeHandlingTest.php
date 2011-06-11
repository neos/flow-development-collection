<?php
namespace F3\FLOW3\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Utility\TypeHandling class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TypeHandlingTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidType() {
		\F3\FLOW3\Utility\TypeHandling::parseType('something not a type');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidElementTypeHint() {
		\F3\FLOW3\Utility\TypeHandling::parseType('string<integer>');
	}

	/**
	 * data provider for parseTypeReturnsArrayWithInformation
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function types() {
		return array(
			array('int', array('type' => 'integer', 'elementType' => NULL)),
			array('string', array('type' => 'string', 'elementType' => NULL)),
			array('DateTime', array('type' => 'DateTime', 'elementType' => NULL)),
			array('F3\Foo\Bar', array('type' => 'F3\Foo\Bar', 'elementType' => NULL)),
			array('\F3\Foo\Bar', array('type' => 'F3\Foo\Bar', 'elementType' => NULL)),
			array('array<integer>', array('type' => 'array', 'elementType' => 'integer')),
			array('ArrayObject<string>', array('type' => 'ArrayObject', 'elementType' => 'string')),
			array('SplObjectStorage<F3\Foo\Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'F3\Foo\Bar')),
			array('SplObjectStorage<\F3\Foo\Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'F3\Foo\Bar')),
		);
	}

	/**
	 * @test
	 * @dataProvider types
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeReturnsArrayWithInformation($type, $expectedResult) {
		$this->assertEquals(
			\F3\FLOW3\Utility\TypeHandling::parseType($type),
			$expectedResult
		);
	}

	/**
	 * data provider for normalizeTypesReturnsNormalizedType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function normalizeTypes() {
		return array(
			array('int', 'integer'),
			array('double', 'float'),
			array('bool', 'boolean'),
			array('string', 'string')
		);
	}

	/**
	 * @test
	 * @dataProvider normalizeTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function normalizeTypesReturnsNormalizedType($type, $normalized) {
		$this->assertEquals(\F3\FLOW3\Utility\TypeHandling::normalizeType($type), $normalized);
	}

	/**
	 * data provider for isLiteralReturnsFalseForNonLiteralTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function nonLiteralTypes() {
		return array(
			array('DateTime'),
			array('\Foo\Bar'),
			array('array'),
			array('ArrayObject'),
			array('stdClass')
		);
	}

	/**
	 * @test
	 * @dataProvider nonliteralTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isLiteralReturnsFalseForNonLiteralTypes($type) {
		$this->assertFalse(\F3\FLOW3\Utility\TypeHandling::isLiteral($type));
	}

	/**
	 * data provider for isLiteralReturnsTrueForLiteralType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function literalTypes() {
		return array(
			array('integer'),
			array('int'),
			array('float'),
			array('double'),
			array('boolean'),
			array('bool'),
			array('string')
		);
	}

	/**
	 * @test
	 * @dataProvider literalTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isLiteralReturnsTrueForLiteralType($type) {
		$this->assertTrue(\F3\FLOW3\Utility\TypeHandling::isLiteral($type));
	}
}
?>
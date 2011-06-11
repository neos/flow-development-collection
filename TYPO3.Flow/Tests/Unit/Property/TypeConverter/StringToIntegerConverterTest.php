<?php
namespace F3\FLOW3\Tests\Unit\Property\TypeConverter;

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
 * Testcase for the String to Integer converter
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers \F3\FLOW3\Property\TypeConverter\StringToIntegerConverter<extended>
 */
class StringToIntegerConverterTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\Property\TypeConverterInterface
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new \F3\FLOW3\Property\TypeConverter\StringToIntegerConverter();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('integer', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function convertFromShouldCastTheStringToInteger() {
		$this->assertSame(15, $this->converter->convertFrom('15', 'integer'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canConvertShouldReturnTrue() {
		$this->assertTrue($this->converter->canConvert('15', 'integer'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertiesShouldReturnEmptyArray() {
		$this->assertEquals(array(), $this->converter->getProperties('myString'));
	}
}
?>
<?php
namespace TYPO3\FLOW3\Tests\Functional\Property;

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
 * Testcase for Property Mapper
 *
 */
class PropertyMapperTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 *
	 * @var \TYPO3\FLOW3\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		parent::setUp();
		$this->propertyMapper = $this->objectManager->get('TYPO3\FLOW3\Property\PropertyMapper');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function domainObjectWithSimplePropertiesCanBeCreated() {
		$source = array(
			'name' => 'Robert Skaarhoj',
			'age' => '25',
			'averageNumberOfKids' => '1.5'
		);

		$result = $this->propertyMapper->convert($source, 'TYPO3\FLOW3\Tests\Functional\Property\Fixtures\TestEntity');
		$this->assertSame('Robert Skaarhoj', $result->getName());
		$this->assertSame(25, $result->getAge());
		$this->assertSame(1.5, $result->getAverageNumberOfKids());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function simpleObjectWithSimplePropertiesCanBeCreated() {
		$source = array(
			'name' => 'Christopher',
			'size' => '187',
			'signedCla' => TRUE
		);

		$result = $this->propertyMapper->convert($source, 'TYPO3\FLOW3\Tests\Functional\Property\Fixtures\TestClass');
		$this->assertSame('Christopher', $result->getName());
		$this->assertSame(187, $result->getSize());
		$this->assertSame(TRUE, $result->getSignedCla());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function valueobjectCanBeMapped() {
		$source = array(
			'__identity' => 'abcdefghijkl',
			'name' => 'Christopher',
			'age' => '28'
		);

		$result = $this->propertyMapper->convert($source, 'TYPO3\FLOW3\Tests\Functional\Property\Fixtures\TestValueobject');
		$this->assertSame('Christopher', $result->getName());
		$this->assertSame(28, $result->getAge());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function integerCanBeMappedToString() {
		$source = array(
			'name' => 42,
			'size' => 23
		);

		$result = $this->propertyMapper->convert($source, 'TYPO3\FLOW3\Tests\Functional\Property\Fixtures\TestClass');
		$this->assertSame('42', $result->getName());
		$this->assertSame(23, $result->getSize());
	}

}
?>
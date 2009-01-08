<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\Editor;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Property_AbstractCompositeConverterTest.php 1711 2009-01-07 21:51:23Z sebastian $
 */

include_once( __DIR__ . '/Fixture/ExampleDomainObject_BlogPosting.php');

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class DomainObjectConverterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var F3\FLOW3\Property\Converter\DomainObjectConverter
	 */
	protected $domainObjectConverter;

	/**
	 * Set testcases up
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$exampleDomainObject = 'F3\FLOW3\Property\Converter\ExampleDomainObject_BlogPosting';
		$this->domainObjectConverter = new \F3\FLOW3\Property\Converter\DomainObjectConverter($exampleDomainObject);

		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$this->domainObjectConverter->injectObjectFactory($this->mockObjectFactory);

		$this->mockPropertyMapper = $this->getMock('F3\FLOW3\Property\Mapper', array(), array(), '', FALSE);
		$this->domainObjectConverter->injectPropertyMapper($this->mockPropertyMapper);
	}

	/**
	 * If you call "setAsFormat" with another argument than array, throw an exception.
	 *
	 * @expectedException F3\FLOW3\Property\Exception\InvalidFormat
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @test
	 */
	public function setAsFormatThrowsExceptionIfWrongFormatGiven() {
		$this->domainObjectConverter->setAsFormat('text', '');
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @test
	 */
	public function setAsFormatCallsPropertyMapperCorrectly() {
		$blogPosting = new \F3\FLOW3\Property\Converter\ExampleDomainObject_BlogPosting();

		$arrayToMap = array(
			'title' => 'Hallo',
			'contents' => 'These are my contents'
		);

		$this->mockObjectFactory->expects($this->once())->method('create')->with($this->equalTo('F3\FLOW3\Property\Converter\ExampleDomainObject_BlogPosting'))->will($this->returnValue($blogPosting));
		$this->mockPropertyMapper->expects($this->once())->method('map')->with($this->equalTo(new \ArrayObject($arrayToMap)), $this->equalTo($blogPosting));

		$this->domainObjectConverter->setAsFormat('array', $arrayToMap);
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @test
	 */
	public function setAsFormatSetsIdentifier() {
		$blogPosting = new \F3\FLOW3\Property\Converter\ExampleDomainObject_BlogPosting();

		$identifier = '550e8400-e29b-11d4-a716-446655440091';

		$arrayToMap = array(
			'identifier' => $identifier,
			'title' => 'Hallo',
			'contents' => 'These are my contents'
		);

		$this->mockObjectFactory->expects($this->once())->method('create')->with($this->equalTo('F3\FLOW3\Property\Converter\ExampleDomainObject_BlogPosting'))->will($this->returnValue($blogPosting));
		$this->mockPropertyMapper->expects($this->once())->method('map')->with($this->equalTo(new \ArrayObject($arrayToMap)), $this->equalTo($blogPosting));

		$this->domainObjectConverter->setAsFormat('array', $arrayToMap);

		$this->assertEquals($identifier, $this->domainObjectConverter->getIdentifier(), 'Identiifer was not extracted');
	}
}

?>
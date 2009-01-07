<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the abstract extensible editor
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AbstractCompositeConverterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registeringANewFormatBasicallyWorks() {
		$compositeConverter = $this->getMock('F3\FLOW3\Property\Converter\AbstractCompositeConverter', array('setProperty', 'getProperty', 'getSupportedFormats'));
		$compositeConverter->registerNewFormat('someNewFormat', $this->getMock('F3\FLOW3\Property\ConverterInterface'));
		$compositeConverter->removeFormat('someNewFormat');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidFormat
	 */
	public function removingANotExistingFormatThrowsException() {
		$compositeConverter = $this->getMock('F3\FLOW3\Property\Converter\AbstractCompositeConverter', array('setProperty', 'getProperty', 'getSupportedFormats'));

		$compositeConverter->removeFormat('someNotExistingFormat');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function settingThePropertyWithAnExtendedFormatCallsTheCorrectConverter() {
		$compositeConverter = $this->getMock('F3\FLOW3\Property\Converter\AbstractCompositeConverter', array('setProperty', 'getProperty', 'getSupportedFormats'));
		$converterWithNewFormat = $this->getMock('F3\FLOW3\Property\ConverterInterface');
		$converterWithNewFormat->expects($this->once())->method('setAsFormat')->with($this->equalTo('newFormat'), $this->equalTo('someProperty'));
		$compositeConverter->registerNewFormat('newFormat', $converterWithNewFormat);

		$compositeConverter->setAsFormat('newFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function gettingThePropertyWithAnExtendedFormatReturnsTheCorrectValueFromTheCorrectConverter() {
		$compositeConverter = $this->getMock('F3\FLOW3\Property\Converter\AbstractCompositeConverter', array('setProperty', 'getProperty', 'getSupportedFormats'));
		$converterWithNewFormat = $this->getMock('F3\FLOW3\Property\ConverterInterface');
		$converterWithNewFormat->expects($this->once())->method('getAsFormat')->with($this->equalTo('newFormat'))->will($this->returnValue('editedProperty'));

		$compositeConverter->registerNewFormat('newFormat', $converterWithNewFormat);

		$this->assertEquals('editedProperty', $compositeConverter->getAsFormat('newFormat'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidFormat
	 */
	public function settingAPropertyAsAnInvalidFormatThrowsException() {
		$compositeConverter = $this->getMock('F3\FLOW3\Property\Converter\AbstractCompositeConverter', array('setProperty', 'getProperty', 'getSupportedFormats'));

		$compositeConverter->setAsFormat('invalidFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidFormat
	 */
	public function gettingAPropertyAsAnInvalidFormatThrowsException() {
		$compositeConverter = $this->getMock('F3\FLOW3\Property\Converter\AbstractCompositeConverter', array('setProperty', 'getProperty', 'getSupportedFormats'));

		$compositeConverter->getAsFormat('invalidFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function endlesRecursionOfCompositeConvertersThrowsException() {
		$this->markTestIncomplete();
	}
}
?>
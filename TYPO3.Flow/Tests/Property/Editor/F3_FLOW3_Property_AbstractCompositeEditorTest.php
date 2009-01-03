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
class AbstractCompositeEditorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registeringANewFormatBasicallyWorks() {
		$compositeEditor = $this->getMock('F3\FLOW3\Property\Editor\AbstractCompositeEditor', array('setProperty', 'getProperty', 'getSupportedFormats'));
		$compositeEditor->registerNewFormat('someNewFormat', $this->getMock('F3\FLOW3\Property\EditorInterface'));
		$compositeEditor->removeFormat('someNewFormat');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidFormat
	 */
	public function removingANotExistingFormatThrowsException() {
		$compositeEditor = $this->getMock('F3\FLOW3\Property\Editor\AbstractCompositeEditor', array('setProperty', 'getProperty', 'getSupportedFormats'));

		$compositeEditor->removeFormat('someNotExistingFormat');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function settingThePropertyWithAnExtendedFormatCallsTheCorrectEditor() {
		$compositeEditor = $this->getMock('F3\FLOW3\Property\Editor\AbstractCompositeEditor', array('setProperty', 'getProperty', 'getSupportedFormats'));
		$editorWithNewFormat = $this->getMock('F3\FLOW3\Property\EditorInterface');
		$editorWithNewFormat->expects($this->once())->method('setAsFormat')->with($this->equalTo('newFormat'), $this->equalTo('someProperty'));
		$compositeEditor->registerNewFormat('newFormat', $editorWithNewFormat);

		$compositeEditor->setAsFormat('newFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function gettingThePropertyWithAnExtendedFormatReturnsTheCorrectValueFromTheCorrectEditor() {
		$compositeEditor = $this->getMock('F3\FLOW3\Property\Editor\AbstractCompositeEditor', array('setProperty', 'getProperty', 'getSupportedFormats'));
		$editorWithNewFormat = $this->getMock('F3\FLOW3\Property\EditorInterface');
		$editorWithNewFormat->expects($this->once())->method('getAsFormat')->with($this->equalTo('newFormat'))->will($this->returnValue('editedProperty'));

		$compositeEditor->registerNewFormat('newFormat', $editorWithNewFormat);

		$this->assertEquals('editedProperty', $compositeEditor->getAsFormat('newFormat'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidFormat
	 */
	public function settingAPropertyAsAnInvalidFormatThrowsException() {
		$compositeEditor = $this->getMock('F3\FLOW3\Property\Editor\AbstractCompositeEditor', array('setProperty', 'getProperty', 'getSupportedFormats'));

		$compositeEditor->setAsFormat('invalidFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidFormat
	 */
	public function gettingAPropertyAsAnInvalidFormatThrowsException() {
		$compositeEditor = $this->getMock('F3\FLOW3\Property\Editor\AbstractCompositeEditor', array('setProperty', 'getProperty', 'getSupportedFormats'));

		$compositeEditor->getAsFormat('invalidFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function endlesRecursionOfCompositeEditorsThrowsException() {
		$this->markTestIncomplete();
	}
}
?>
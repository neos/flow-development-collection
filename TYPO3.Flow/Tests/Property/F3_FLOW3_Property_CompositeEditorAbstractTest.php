<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id: F3_FLOW3_Property_CompositeEditorAbstractTest.php 661 2008-03-25 14:03:49Z robert $
 */

/**
 * Testcase for the abstract extensible editor
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Property_CompositeEditorAbstractTest.php 661 2008-03-25 14:03:49Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Property_CompositeEditorAbstractTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registeringANewFormatBasicallyWorks() {
		$compositeEditor = $this->getMock('F3_FLOW3_Property_Editor_CompositeEditorAbstract', array('setProperty', 'getProperty', 'setAsString', 'getAsString', 'getSupportedFormats', '__toString'));
		$compositeEditor->registerNewFormat('someNewFormat', $this->getMock('F3_FLOW3_Property_EditorInterface'));
		$compositeEditor->removeFormat('someNewFormat');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingANotExistingFormatThrowsException() {
		$compositeEditor = $this->getMock('F3_FLOW3_Property_Editor_CompositeEditorAbstract', array('setProperty', 'getProperty', 'setAsString', 'getAsString', 'getSupportedFormats', '__toString'));

		try {
			$compositeEditor->removeFormat('someNotExistingFormat');
			$this->fail('No exception has been thrown on removing a not existing format.');
		} catch (F3_FLOW3_Property_Exception_InvalidFormat $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function settingThePropertyWithAnExtendedFormatCallsTheCorrectEditor() {
		$compositeEditor = $this->getMock('F3_FLOW3_Property_Editor_CompositeEditorAbstract', array('setProperty', 'getProperty', 'setAsString', 'getAsString', 'getSupportedFormats', '__toString'));
		$editorWithNewFormat = $this->getMock('F3_FLOW3_Property_EditorInterface');
		$editorWithNewFormat->expects($this->once())->method('setAs')->with($this->equalTo('newFormat'), $this->equalTo('someProperty'));
		$compositeEditor->registerNewFormat('newFormat', $editorWithNewFormat);

		$compositeEditor->setAs('newFormat', 'someProperty');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function gettingThePropertyWithAnExtendedFormatReturnsTheCorrectValueFromTheCorrectEditor() {
		$compositeEditor = $this->getMock('F3_FLOW3_Property_Editor_CompositeEditorAbstract', array('setProperty', 'getProperty', 'setAsString', 'getAsString', 'getSupportedFormats', '__toString'));
		$editorWithNewFormat = $this->getMock('F3_FLOW3_Property_EditorInterface');
		$editorWithNewFormat->expects($this->once())->method('getAs')->with($this->equalTo('newFormat'))->will($this->returnValue('editedProperty'));

		$compositeEditor->registerNewFormat('newFormat', $editorWithNewFormat);

		$this->assertEquals('editedProperty', $compositeEditor->getAs('newFormat'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function settingAPropertyAsAnInvalidFormatThrowsException() {
		$compositeEditor = $this->getMock('F3_FLOW3_Property_Editor_CompositeEditorAbstract', array('setProperty', 'getProperty', 'setAsString', 'getAsString', 'getSupportedFormats', '__toString'));

		try {
			$compositeEditor->setAs('invalidFormat', 'someProperty');
			$this->fail('No exception has been thrown on setting an invalid format');
		} catch(F3_FLOW3_Property_Exception_InvalidFormat $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function gettingAPropertyAsAnInvalidFormatThrowsException() {
		$compositeEditor = $this->getMock('F3_FLOW3_Property_Editor_CompositeEditorAbstract', array('setProperty', 'getProperty', 'setAsString', 'getAsString', 'getSupportedFormats', '__toString'));

		try {
			$compositeEditor->getAs('invalidFormat', 'someProperty');
			$this->fail('No exception has been thrown on getting an invalid format');
		} catch(F3_FLOW3_Property_Exception_InvalidFormat $exception) {

		}
	}

	public function endlesRecursionOfCompositeEditorsThrowsException() {
		$this->markTestIncomplete();
	}
}
?>
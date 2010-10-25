<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Validation\Validator;

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
 * Testcase for the text validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TextValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\TextValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid('foo');
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorReturnsTrueForASimpleString() {
		$textValidator = new \F3\FLOW3\Validation\Validator\TextValidator();
		$this->assertTrue($textValidator->isValid('this is a very simple string'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textValidatorAllowsTheNewLineCharacter() {
		$sampleText = "Ierd Frot uechter mä get, Kirmesdag Milliounen all en, sinn main Stréi mä och. \nVu dan durch jéngt gréng, ze rou Monn voll stolz. \nKe kille Minutt d'Kirmes net. Hir Wand Lann Gaas da, wär hu Heck Gart zënter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dénen d'Gaassen eng, eng am virun geplot d'Lëtzebuerger, get botze rëscht Blieder si. Dat Dauschen schéinste Milliounen fu. Ze riede méngem Keppchen déi, si gét fergiess erwaacht, räich jéngt duerch en nun. Gëtt Gaas d'Vullen hie hu, laacht Grénge der dé. Gemaacht gehéiert da aus, gutt gudden d'wäiss mat wa.";
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($textValidator->isValid($sampleText));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textValidatorAllowsCommonSpecialCharacters() {
		$sampleText = "3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called 'quote'.";
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($textValidator->isValid($sampleText));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorReturnsFalseForAStringWithHtml() {
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities() {
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->once())->method('addError');
		$textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>');
	}
}

?>
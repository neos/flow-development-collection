<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TextValidatorTest extends \F3\Testing\BaseTestCase {

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
	public function textValidatorReturnsFalseForAStringWithHTML() {
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function textValidatorReturnsFalseForAStringWithPercentEncodedHTML() {
		$this->markTestIncomplete('The text validator currently allows percent encoded HTML!');
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid('%3cspan style="color: #BBBBBB;"%3ea nice text%3c/span%3e'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHTMLEntities() {
		$textValidator = $this->getMock('F3\FLOW3\Validation\Validator\TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->once())->method('addError')->with('The given subject was not a valid text (contained XML tags). Got: "<span style="color: #BBBBBB;">a nice text</span>"', 1221565786);
		$textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>');
	}
}

?>
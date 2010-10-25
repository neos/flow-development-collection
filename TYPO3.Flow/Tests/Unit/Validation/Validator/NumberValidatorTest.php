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
 * Testcase for the number validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en_GB');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$sampleNumber = 1;
		$mockNumberParser = $this->getMock('F3\FLOW3\I18n\Parser\NumberParser');
		$mockNumberParser->expects($this->once())->method('parseDecimalNumber', $sampleNumber)->will($this->returnValue(TRUE));

		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\NumberValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));

		$validator->injectNumberParser($mockNumberParser);
		$validator->setOptions(array('locale' => $this->sampleLocale));

		$validator->isValid($sampleNumber);
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$sampleInvalidNumber = 'this is not a number';
		$mockNumberParser = $this->getMock('F3\FLOW3\I18n\Parser\NumberParser');
		$mockNumberParser->expects($this->once())->method('parseDecimalNumber', $sampleInvalidNumber)->will($this->returnValue(FALSE));

		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\NumberValidator', array('addError'));
		$validator->expects($this->once())->method('addError');

		$validator->injectNumberParser($mockNumberParser);
		$validator->setOptions(array('locale' => $this->sampleLocale));

		$validator->isValid($sampleInvalidNumber);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsFalseForIncorrectValues() {
		$sampleInvalidNumber = 'this is not a number';
		$mockNumberParser = $this->getMock('F3\FLOW3\I18n\Parser\NumberParser');
		$mockNumberParser->expects($this->once())->method('parsePercentNumber', $sampleInvalidNumber)->will($this->returnValue(FALSE));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('create', 'F3\FLOW3\I18n\Locale', 'en_GB')->will($this->returnValue($this->sampleLocale));
		$mockObjectManager->expects($this->at(1))->method('create', 'F3\FLOW3\Validation\Error');

		$validator = new \F3\FLOW3\Validation\Validator\NumberValidator();
		$validator->injectNumberParser($mockNumberParser);
		$validator->injectObjectManager($mockObjectManager);
		$validator->setOptions(array('locale' => 'en_GB', 'formatLength' => \F3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \F3\FLOW3\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT));

		$this->assertFalse($validator->isValid($sampleInvalidNumber));
	}
}

?>
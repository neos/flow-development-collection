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
 * Testcase for the DateTime validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DateTimeValidatorTest extends \F3\FLOW3\Tests\UnitTestCase {

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
		$sampleDate = '10.08.2010';
		$mockDatetimeParser = $this->getMock('F3\FLOW3\I18n\Parser\DatetimeParser');
		$mockDatetimeParser->expects($this->once())->method('parseDate', $sampleDate)->will($this->returnValue(TRUE));

		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\DateTimeValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));

		$validator->injectDatetimeParser($mockDatetimeParser);
		$validator->setOptions(array('locale' => $this->sampleLocale));

		$validator->isValid($sampleDate);
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsFalseForIncorrectValues() {
		$sampleInvalidTime = 'this is not a time string';
		$mockDatetimeParser = $this->getMock('F3\FLOW3\I18n\Parser\DatetimeParser');
		$mockDatetimeParser->expects($this->once())->method('parseTime', $sampleInvalidTime)->will($this->returnValue(FALSE));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('create', 'F3\FLOW3\I18n\Locale', 'en_GB')->will($this->returnValue($this->sampleLocale));
		$mockObjectManager->expects($this->at(1))->method('create', 'F3\FLOW3\Validation\Error');

		$validator = new \F3\FLOW3\Validation\Validator\DateTimeValidator();
		$validator->injectDatetimeParser($mockDatetimeParser);
		$validator->injectObjectManager($mockObjectManager);
		$validator->setOptions(array('locale' => 'en_GB', 'formatLength' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_TIME));

		$this->assertFalse($validator->isValid($sampleInvalidTime));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsTrueForCorrectValues() {
		$sampleValidDateTime = '10.08.2010, 18:00 CEST';
		$mockDatetimeParser = $this->getMock('F3\FLOW3\I18n\Parser\DatetimeParser');
		$mockDatetimeParser->expects($this->once())->method('parseDateAndTime', $sampleValidDateTime)->will($this->returnValue(array('parsed datetime')));

		$mockLocalizationService = $this->getMock('F3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->once())->method('getDefaultLocale')->will($this->returnValue($this->sampleLocale));

		$validator = new \F3\FLOW3\Validation\Validator\DateTimeValidator();
		$validator->injectDatetimeParser($mockDatetimeParser);
		$validator->injectLocalizationService($mockLocalizationService);
		$validator->setOptions(array('formatLength' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_LENGTH_FULL, 'formatType' => \F3\FLOW3\I18n\Cldr\Reader\DatesReader::FORMAT_TYPE_DATETIME));

		$this->assertTrue($validator->isValid($sampleValidDateTime));
	}
}

?>
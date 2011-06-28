<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\TranslationProvider;

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
 * Testcase for the XliffTranslationProvider
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XliffTranslationProviderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $sampleSourceName;

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @var \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader
	 */
	protected $mockPluralsReader;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->sampleSourceName = 'foo';
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en_GB');

		$this->mockPluralsReader = $this->getMock('TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$this->mockPluralsReader->expects($this->once())->method('getPluralForms')->with($this->sampleLocale)->will($this->returnValue(array(\TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_OTHER)));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsTranslatedLabelWhenOriginalLabelProvided() {
		$mockModel = $this->getMock('TYPO3\FLOW3\I18n\Xliff\XliffModel', array(), array('foo'));
		$mockModel->expects($this->once())->method('getTargetBySource')->with('bar', 0)->will($this->returnValue('baz'));

		$translationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$translationProvider->injectPluralsReader($this->mockPluralsReader);
		$translationProvider->expects($this->once())->method('getModel')->with($this->sampleSourceName, $this->sampleLocale)->will($this->returnValue($mockModel));

		$result = $translationProvider->getTranslationByOriginalLabel($this->sampleSourceName, 'bar', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE);
		$this->assertEquals('baz', $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsTranslatedLabelWhenLabelIdProvided() {
		$mockModel = $this->getMock('TYPO3\FLOW3\I18n\Xliff\XliffModel', array(), array('foo'));
		$mockModel->expects($this->once())->method('getTargetByTransUnitId')->with('bar', 1)->will($this->returnValue('baz'));

		$translationProvider = $this->getAccessibleMock('TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$translationProvider->injectPluralsReader($this->mockPluralsReader);
		$translationProvider->expects($this->once())->method('getModel')->with($this->sampleSourceName, $this->sampleLocale)->will($this->returnValue($mockModel));

		$result = $translationProvider->getTranslationById($this->sampleSourceName, 'bar', $this->sampleLocale);
		$this->assertEquals('baz', $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\TranslationProvider\Exception\InvalidPluralFormException
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function throwsExceptionWhenInvalidPluralFormProvided() {
		$translationProvider = new \TYPO3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider();
		$translationProvider->injectPluralsReader($this->mockPluralsReader);

		$translationProvider->getTranslationByOriginalLabel($this->sampleSourceName, 'bar', $this->sampleLocale, \TYPO3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_FEW);
	}
}

?>
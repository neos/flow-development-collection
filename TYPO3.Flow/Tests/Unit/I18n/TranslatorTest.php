<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n;

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
 * Testcase for the Translator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TranslatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @var \F3\FLOW3\I18n\Translator
	 */
	protected $translator;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->sampleLocale = new \F3\FLOW3\I18n\Locale('en_GB');

		$mockLocalizationService = $this->getMock('F3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->once())->method('getDefaultLocale')->will($this->returnValue($this->sampleLocale));

		$mockPluralsReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForm', 1, $this->sampleLocale)->will($this->returnValue(\F3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE));

		$this->translator = new \F3\FLOW3\I18n\Translator();
		$this->translator->injectLocalizationService($mockLocalizationService);
		$this->translator->injectPluralsReader($mockPluralsReader);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function translatingIsDoneCorrectly() {
		$mockTranslationProvicer = $this->getAccessibleMock('F3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvicer->expects($this->once())->method('getTranslationByOriginalLabel', 'source', 'Untranslated label', $this->sampleLocale, \F3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE)->will($this->returnValue('Translated label'));

		$mockFormatResolver = $this->getMock('F3\FLOW3\I18n\FormatResolver');
		$mockFormatResolver->expects($this->once())->method('resolvePlaceholders', 'Translated label', array('value1', 'value2'), $this->sampleLocale)->will($this->returnValue('Formatted and translated label'));

		$this->translator->injectTranslationProvider($mockTranslationProvicer);
		$this->translator->injectFormatResolver($mockFormatResolver);

		$result = $this->translator->translateByOriginalLabel('Untranslated label', 'source', array('value1', 'value2'), 1);
		$this->assertEquals('Formatted and translated label', $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function returnsOriginalLabelOrIdWhenTranslationNotAvailable() {
		$mockTranslationProvicer = $this->getAccessibleMock('F3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider');
		$mockTranslationProvicer->expects($this->once())->method('getTranslationByOriginalLabel', 'source', 'id', $this->sampleLocale, \F3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE)->will($this->returnValue(FALSE));
		$mockTranslationProvicer->expects($this->once())->method('getTranslationById', 'source', 'id', $this->sampleLocale, \F3\FLOW3\I18n\Cldr\Reader\PluralsReader::RULE_ONE)->will($this->returnValue(FALSE));

		$this->translator->injectTranslationProvider($mockTranslationProvicer);

		$result = $this->translator->translateByOriginalLabel('original label', 'source', array(), 1);
		$this->assertEquals('original label', $result);

		$result = $this->translator->translateById('id', 'source', array(), NULL, $this->sampleLocale);
		$this->assertEquals('id', $result);
	}
}

?>
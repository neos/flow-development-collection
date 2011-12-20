<?php
namespace TYPO3\FLOW3\Tests\Functional\I18n;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the I18N translations
 *
 */
class TranslatorTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Translator
	 */
	protected $translator;

	/**
	 * Initialize dependencies
	 */
	public function setUp() {
		parent::setUp();
		$this->translator = $this->objectManager->get('TYPO3\FLOW3\I18n\Translator');
	}

	/**
	 * @return array
	 */
	public function idAndLocaleForTranslation() {
		return array(
			array('update', new \TYPO3\FLOW3\I18n\Locale('en'), 'Update'),
			array('update', new \TYPO3\FLOW3\I18n\Locale('de'), 'Aktualisieren')
		);
	}

	/**
	 * @test
	 * @dataProvider idAndLocaleForTranslation
	 */
	public function simpleTranslationByIdWorks($id, $locale, $translation) {
		$result = $this->translator->translateById($id, array(), NULL, $locale, 'Main', 'TYPO3.FLOW3');
		$this->assertEquals($translation, $result);
	}

	/**
	 * @return array
	 */
	public function labelAndLocaleForTranslation() {
		return array(
			array('Update', new \TYPO3\FLOW3\I18n\Locale('en'), 'Update'),
			array('Update', new \TYPO3\FLOW3\I18n\Locale('de'), 'Aktualisieren')
		);
	}

	/**
	 * @test
	 * @dataProvider labelAndLocaleForTranslation
	 */
	public function simpleTranslationByLabelWorks($label, $locale, $translation) {
		$result = $this->translator->translateByOriginalLabel($label, array(), NULL, $locale, 'Main', 'TYPO3.FLOW3');
		$this->assertEquals($translation, $result);
	}

}
?>
<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Cldr\Reader;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\I18n\Cldr\Reader\PluralsReader;

/**
 * Testcase for the PluralsReader
 *
 */
class PluralsReaderTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\Reader\PluralsReader
	 */
	protected $reader;

	/**
	 * @return void
	 */
	public function setUp() {
		$samplePluralRulesData = array(
			'pluralRules[@locales="ro mo"]' => array(
				'pluralRule[@count="one"]' => 'n is 1',
				'pluralRule[@count="few"]' => 'n is 0 OR n is not 1 AND n mod 100 in 1..19',
			),
		);

		$mockModel = $this->getAccessibleMock('TYPO3\Flow\I18n\Cldr\CldrModel', array('getRawArray'), array(array('fake/path')));
		$mockModel->expects($this->once())->method('getRawArray')->with('plurals')->will($this->returnValue($samplePluralRulesData));

		$mockRepository = $this->getMock('TYPO3\Flow\I18n\Cldr\CldrRepository');
		$mockRepository->expects($this->once())->method('getModel')->with('supplemental/plurals')->will($this->returnValue($mockModel));

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->at(0))->method('has')->with('rulesets')->will($this->returnValue(FALSE));
		$mockCache->expects($this->at(1))->method('set')->with('rulesets');
		$mockCache->expects($this->at(2))->method('set')->with('rulesetsIndices');

		$this->reader = new PluralsReader();
		$this->reader->injectCldrRepository($mockRepository);
		$this->reader->injectCache($mockCache);
		$this->reader->initializeObject();
	}

	/**
	 * Data provider for returnsCorrectPluralForm
	 *
	 * @return array
	 */
	public function quantities() {
		return array(
			array(1, PluralsReader::RULE_ONE),
			array(2, PluralsReader::RULE_FEW),
			array(100, PluralsReader::RULE_OTHER),
			array(101, PluralsReader::RULE_FEW),
			array(101.1, PluralsReader::RULE_OTHER),
		);
	}

	/**
	 * @test
	 * @dataProvider quantities
	 */
	public function returnsCorrectPluralForm($quantity, $pluralForm) {
		$locale = new \TYPO3\Flow\I18n\Locale('mo');

		$result = $this->reader->getPluralForm($quantity, $locale);
		$this->assertEquals($pluralForm, $result);
	}
}

<?php
namespace TYPO3\Flow\Tests\Functional\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Float converter
 *
 */
class FloatConverterTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\Flow\Property\TypeConverter\FloatConverter
	 */
	protected $converter;

	public function setUp() {
		parent::setUp();
		$this->converter = $this->objectManager->get('TYPO3\Flow\Property\TypeConverter\FloatConverter');
	}

	/**
	 * @return array Signature: string $locale, string $source, float $expectedResult
	 */
	public function localeParsingDataProvider() {
		return array(
			array('de', '13,20', 13.2),
			array('de', '112,777', 112.777),
			array('de', '10.423,58', 10423.58),

			array('en', '14.42', 14.42),
			array('en', '10,423.58', 10423.58),
			array('en', '10,42358', 1042358),
		);
	}

	/**
	 * @test
	 * @dataProvider localeParsingDataProvider
	 *
	 * @param \TYPO3\Flow\I18n\Locale|string $locale
	 * @param $source
	 * @param $expectedResult
	 */
	public function convertFromUsingVariousLocalesParsesFloatCorrectly($locale, $source, $expectedResult) {
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', $locale);

		$actualResult = $this->converter->convertFrom($source, 'float', array(), $configuration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromReturnsErrorIfFormatIsInvalid() {
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', 'de');

		$actualResult = $this->converter->convertFrom('12,777777', 'float', array(), $configuration);
		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);

		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $this->converter->convertFrom('84,00', 'float'));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\I18n\Exception\InvalidLocaleIdentifierException
	 */
	public function convertFromThrowsExceptionIfLocaleIsInvalid() {
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', 'some-non-existent-locale-identifier');

		$this->converter->convertFrom('84,42', 'float', array(), $configuration);
	}

	/**
	 * @test
	 */
	public function convertFromDoesntUseLocaleParserIfNoConfigurationGiven() {
		$this->assertEquals(84, $this->converter->convertFrom('84.000', 'float'));
		$this->assertEquals(84.42, $this->converter->convertFrom('84.42', 'float'));
	}
}

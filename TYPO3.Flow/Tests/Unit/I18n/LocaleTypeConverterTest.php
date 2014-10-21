<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\LocaleTypeConverter;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Locale type converter
 *
 * @covers \TYPO3\Flow\I18n\LocaleTypeConverter<extended>
 */
class LocaleTypeConverterTest extends UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Property\TypeConverterInterface
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new LocaleTypeConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('TYPO3\Flow\I18n\Locale', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromShouldReturnLocale() {
		$this->assertInstanceOf('TYPO3\Flow\I18n\Locale', $this->converter->convertFrom('de', 'irrelevant'));
	}

	/**
	 * @test
	 */
	public function canConvertFromShouldReturnTrue() {
		$this->assertTrue($this->converter->canConvertFrom('de', 'TYPO3\Flow\I18n\Locale'));
	}

	/**
	 * @test
	 */
	public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray() {
		$this->assertEmpty($this->converter->getSourceChildPropertiesToBeConverted('something'));
	}

}

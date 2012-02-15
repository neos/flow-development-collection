<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n;

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
 * Testcase for the FormatResolver
 *
 */
class FormatResolverTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->sampleLocale = new \TYPO3\FLOW3\I18n\Locale('en_GB');
	}

	/**
	 * @test
	 */
	public function placeholdersAreResolvedCorrectly() {
		$mockNumberFormatter = $this->getMock('TYPO3\FLOW3\I18n\Formatter\NumberFormatter');
		$mockNumberFormatter->expects($this->at(0))->method('format')->with(1, $this->sampleLocale)->will($this->returnValue('1.0'));
		$mockNumberFormatter->expects($this->at(1))->method('format')->with(2, $this->sampleLocale, array('percent'))->will($this->returnValue('200%'));

		$formatResolver = $this->getAccessibleMock('TYPO3\FLOW3\I18n\FormatResolver', array('getFormatter'));
		$formatResolver->expects($this->exactly(2))->method('getFormatter')->with('number')->will($this->returnValue($mockNumberFormatter));

		$result = $formatResolver->resolvePlaceholders('Foo {0,number}, bar {1,number,percent}', array(1, 2), $this->sampleLocale);
		$this->assertEquals('Foo 1.0, bar 200%', $result);
	}

	/**
	 * @test
	 */
	public function returnsStringCastedArgumentWhenFormatterNameIsNotSet() {
		$formatResolver = new \TYPO3\FLOW3\I18n\FormatResolver();
		$result = $formatResolver->resolvePlaceholders('{0}', array(123), $this->sampleLocale);
		$this->assertEquals('123', $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\Exception\InvalidFormatPlaceholderException
	 */
	public function throwsExceptionWhenInvalidPlaceholderEncountered() {
		$formatResolver = new \TYPO3\FLOW3\I18n\FormatResolver();
		$formatResolver->resolvePlaceholders('{0,damaged {1}', array(), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\Exception\IndexOutOfBoundsException
	 */
	public function throwsExceptionWhenInsufficientNumberOfArgumentsProvided() {
		$formatResolver = new \TYPO3\FLOW3\I18n\FormatResolver();
		$formatResolver->resolvePlaceholders('{0}', array(), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\I18n\Exception\UnknownFormatterException
	 */
	public function throwsExceptionWhenFormatterDoesNotExist() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\FLOW3\I18n\Formatter\FooFormatter')->will($this->throwException(new \TYPO3\FLOW3\I18n\Exception\UnknownFormatterException()));

		$formatResolver = new \TYPO3\FLOW3\I18n\FormatResolver();
		$formatResolver->injectObjectManager($mockObjectManager);

		$formatResolver->resolvePlaceholders('{0,foo}', array(123), $this->sampleLocale);
	}
}

?>
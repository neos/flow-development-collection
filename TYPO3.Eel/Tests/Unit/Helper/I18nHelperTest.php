<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Helper\I18nHelper;
use TYPO3\Eel\Helper\I18n\TranslationParameterTokenFactory;
use TYPO3\Eel\Helper\I18n\TranslationParameterToken;

/**
 * Tests for I18nHelper
 */
class I18nHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Eel\Helper\I18n\TranslationParameterTokenFactory
	 */
	protected $mockTranslationParameterTokenFactory;

	/**
	 * @var \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	protected $mockTranslationParameterToken;

	/**
	 * @var \TYPO3\Eel\Helper\I18nHelper
	 */
	protected $i18nHelper;

	public function setUp() {
		$this->i18nHelper = new I18nHelper();
		$this->mockTranslationParameterTokenFactory = $this->getMockBuilder(TranslationParameterTokenFactory::class)
		->disableOriginalConstructor()
		->getMock();

		$this->inject($this->i18nHelper, 'translationParameterTokenFactory', $this->mockTranslationParameterTokenFactory);
	}

	/**
	 * @test
	 */
	public function translateReturnsCorrectlyConfiguredTranslationParameterTokenWhenCalledWithLongArgumentList() {
		$mockTranslationParameterToken = $this->getMockBuilder(TranslationParameterToken::class)
			->disableOriginalConstructor()
			->getMock();

		$mockTranslationParameterToken->expects($this->once())
			->method('id', 'SomeId')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('value', 'SomeValue')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('arguments', array('a', 'couple', 'of', 'arguments'))
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('source', 'SomeSource')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('package', 'Some.PackageKey')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('quantity', 42)
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('locale', 'SomeLocale')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('translate')
			->willReturn('I am a translation result');

		$this->mockTranslationParameterTokenFactory->expects($this->once())
			->method('create')
			->willReturn($mockTranslationParameterToken);

		$result = $this->i18nHelper->translate('SomeId', 'SomeValue', array('a', 'couple', 'of', 'arguments'), 'SomeSource', 'Some.PackageKey', 42, 'SomeLocale');
		$this->assertEquals('I am a translation result', $result);
	}

	/**
	 * @test
	 */
	public function translateReturnsCorrectlyConfiguredTranslationParameterTokenWhenCalledWithShortHandString() {
		$mockTranslationParameterToken = $this->getMockBuilder(TranslationParameterToken::class)
			->disableOriginalConstructor()
			->getMock();

		$mockTranslationParameterToken->expects($this->once())
			->method('source', 'SomeSource')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('package', 'Some.PackageKey')
			->willReturn($mockTranslationParameterToken);

		$mockTranslationParameterToken->expects($this->once())
			->method('translate')
			->willReturn('I am a translation result');

		$this->mockTranslationParameterTokenFactory->expects($this->once())
			->method('createWithId', 'SomeId')
			->willReturn($mockTranslationParameterToken);

		$result = $this->i18nHelper->translate('Some.PackageKey:SomeSource:SomeId');
		$this->assertEquals('I am a translation result', $result);
	}

	/**
	 * @test
	 */
	public function idReturnsTranslationParameterTokenWithPreconfiguredId() {
		$this->mockTranslationParameterTokenFactory->expects($this->once())
			->method('createWithId', 'SomeId')
			->willReturn('TranslationParameterTokenWithPreconfiguredId');

		$result = $this->i18nHelper->id('SomeId');
		$this->assertEquals('TranslationParameterTokenWithPreconfiguredId', $result);
	}

	/**
	 * @test
	 */
	public function valueReturnsTranslationParameterTokenWithPreconfiguredValue() {
		$this->mockTranslationParameterTokenFactory->expects($this->once())
			->method('createWithValue', 'SomeValue')
			->willReturn('TranslationParameterTokenWithPreconfiguredValue');

		$result = $this->i18nHelper->value('SomeValue');
		$this->assertEquals('TranslationParameterTokenWithPreconfiguredValue', $result);
	}

}

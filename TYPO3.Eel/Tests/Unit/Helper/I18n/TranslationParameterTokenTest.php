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

use TYPO3\Eel\Helper\I18n\TranslationParameterToken;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Translator;

/**
 * Tests for TranslationParameterToken
 */
class TranslationParameterTokenTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var Translator
	 */
	protected $mockTranslator;

	/**
	 * @var Locale
	 */
	protected $dummyLocale;

	public function setUp() {
		$this->mockTranslator = $this->getMockBuilder(Translator::class)
			->disableOriginalConstructor()->getMock();

		$this->dummyLocale = new Locale('de_DE');
	}

	/**
	 * @test
	 */
	public function chainingWorksOnAllRelevantMethods() {
		$translateParameterToken = new TranslationParameterToken();

		$this->assertEquals($translateParameterToken, $translateParameterToken->id(''));
		$this->assertEquals($translateParameterToken, $translateParameterToken->value(''));
		$this->assertEquals($translateParameterToken, $translateParameterToken->arguments(array()));
		$this->assertEquals($translateParameterToken, $translateParameterToken->source(''));
		$this->assertEquals($translateParameterToken, $translateParameterToken->package(''));
		$this->assertEquals($translateParameterToken, $translateParameterToken->quantity(''));
		$this->assertEquals($translateParameterToken, $translateParameterToken->locale('de_DE'));
	}

	/**
	 * @test
	 */
	public function takesAllRelevantParametersIntoAccountForTranslationById() {
		$translateParameterToken = new TranslationParameterToken();
		$this->inject($translateParameterToken, 'translator', $this->mockTranslator);

		$this->mockTranslator->expects($this->once())
			->method('translateById', 'SomeId', array('a', 'couple', 'of', 'arguments'), 42, 'de_DE', 'SomeSource', 'Some.PackageKey')
			->willReturn('I am translated by id.');

		$result = $translateParameterToken
			->id('SomeId')
			->arguments(array('a', 'couple', 'of', 'arguments'))
			->quantity(42)
			->locale('de_DE')
			->source('SomeSource')
			->package('Some.PackageKey')
			->translate();

		$this->assertEquals('I am translated by id.', $result);
	}

	/**
	 * @test
	 */
	public function takesAllRelevantParametersIntoAccountForTranslationByOriginalLabel() {
		$translateParameterToken = new TranslationParameterToken();
		$this->inject($translateParameterToken, 'translator', $this->mockTranslator);

		$this->mockTranslator->expects($this->once())
			->method('translateByOriginalLabel', 'SomeLabel', array('a', 'couple', 'of', 'arguments'), 42, 'de_DE', 'SomeSource', 'Some.PackageKey')
			->willReturn('I am translated by original label.');

		$result = $translateParameterToken
			->value('SomeLabel')
			->arguments(array('a', 'couple', 'of', 'arguments'))
			->quantity(42)
			->locale('de_DE')
			->source('SomeSource')
			->package('Some.PackageKey')
			->translate();

		$this->assertEquals('I am translated by original label.', $result);
	}

	/**
	 * @test
	 */
	public function fallsBackToTranslationByValueIfNoTranslationCouldBeFoundForId() {
		$translateParameterToken = new TranslationParameterToken();
		$this->inject($translateParameterToken, 'translator', $this->mockTranslator);

		$this->mockTranslator->expects($this->once())
			->method('translateById', 'SomeId', array('a', 'couple', 'of', 'arguments'), 42, 'de_DE', 'SomeSource', 'Some.PackageKey')
			->willReturn('SomeId');

		$this->mockTranslator->expects($this->once())
			->method('translateByOriginalLabel', 'SomeLabel', array('a', 'couple', 'of', 'arguments'), 42, 'de_DE', 'SomeSource', 'Some.PackageKey')
			->willReturn('I am translated by original label.');

		$result = $translateParameterToken
			->id('SomeId')
			->value('SomeLabel')
			->arguments(array('a', 'couple', 'of', 'arguments'))
			->quantity(42)
			->locale('de_DE')
			->source('SomeSource')
			->package('Some.PackageKey')
			->translate();

		$this->assertEquals('I am translated by original label.', $result);
	}

	/**
	 * @test
	 */
	public function fallsBackToIdIfNoTranslationCouldBeFoundForIdAndValueIsNotSet() {
		$translateParameterToken = new TranslationParameterToken();
		$this->inject($translateParameterToken, 'translator', $this->mockTranslator);

		$this->mockTranslator->expects($this->once())
			->method('translateById', 'SomeId', array('a', 'couple', 'of', 'arguments'), 42, 'de_DE', 'SomeSource', 'Some.PackageKey')
			->willReturn('SomeId');

		$result = $translateParameterToken
			->id('SomeId')
			->arguments(array('a', 'couple', 'of', 'arguments'))
			->quantity(42)
			->locale('de_DE')
			->source('SomeSource')
			->package('Some.PackageKey')
			->translate();

		$this->assertEquals('SomeId', $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Eel\Exception
	 */
	public function localeThrowsExceptionIfNoValidLocaleIdentifierIsProvided() {
		$translateParameterToken = new TranslationParameterToken();
		$translateParameterToken->locale('INVALIDLOCALE');
	}

}

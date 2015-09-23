<?php
namespace TYPO3\Flow\Tests\Unit\I18n\EelHelper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\I18n\EelHelper\TranslationHelper;
use TYPO3\Flow\I18n\EelHelper\TranslationParameterToken;

/**
 * Tests for TranslateHelper
 */
class TranslationHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function translateReturnsCorrectlyConfiguredTranslationParameterTokenWhenCalledWithLongArgumentList()
    {
        $mockTranslationParameterToken = $this->getMockBuilder(TranslationParameterToken::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $mockTranslationHelper = $this->getMock(TranslationHelper::class, array('createTranslationParameterToken'));
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn($mockTranslationParameterToken);


        $result = $mockTranslationHelper->translate('SomeId', 'SomeValue', array('a', 'couple', 'of', 'arguments'), 'SomeSource', 'Some.PackageKey', 42, 'SomeLocale');
        $this->assertEquals('I am a translation result', $result);
    }

    /**
     * @test
     */
    public function translateReturnsCorrectlyConfiguredTranslationParameterTokenWhenCalledWithShortHandString()
    {
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

        $mockTranslationHelper = $this->getMock(TranslationHelper::class, array('createTranslationParameterToken'));
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn($mockTranslationParameterToken);

        $result = $mockTranslationHelper->translate('Some.PackageKey:SomeSource:SomeId');
        $this->assertEquals('I am a translation result', $result);
    }

    /**
     * @test
     */
    public function idReturnsTranslationParameterTokenWithPreconfiguredId()
    {
        $mockTranslationHelper = $this->getMock(TranslationHelper::class, array('createTranslationParameterToken'));
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn('TranslationParameterTokenWithPreconfiguredId');

        $result = $mockTranslationHelper->id('SomeId');
        $this->assertEquals('TranslationParameterTokenWithPreconfiguredId', $result);
    }

    /**
     * @test
     */
    public function valueReturnsTranslationParameterTokenWithPreconfiguredValue()
    {
        $mockTranslationHelper = $this->getMock(TranslationHelper::class, array('createTranslationParameterToken'));
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', null, 'SomeValue')
            ->willReturn('TranslationParameterTokenWithPreconfiguredValue');

        $result = $mockTranslationHelper->value('SomeValue');
        $this->assertEquals('TranslationParameterTokenWithPreconfiguredValue', $result);
    }
}

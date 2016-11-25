<?php
namespace Neos\Flow\Tests\Unit\I18n\EelHelper;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Flow\I18n\EelHelper\TranslationParameterToken;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for TranslateHelper
 */
class TranslationHelperTest extends UnitTestCase
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
            ->method('arguments', ['a', 'couple', 'of', 'arguments'])
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

        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->setMethods(['createTranslationParameterToken'])->getMock();
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn($mockTranslationParameterToken);


        $result = $mockTranslationHelper->translate('SomeId', 'SomeValue', ['a', 'couple', 'of', 'arguments'], 'SomeSource', 'Some.PackageKey', 42, 'SomeLocale');
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

        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->setMethods(['createTranslationParameterToken'])->getMock();
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
        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->setMethods(['createTranslationParameterToken'])->getMock();
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
        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->setMethods(['createTranslationParameterToken'])->getMock();
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', null, 'SomeValue')
            ->willReturn('TranslationParameterTokenWithPreconfiguredValue');

        $result = $mockTranslationHelper->value('SomeValue');
        $this->assertEquals('TranslationParameterTokenWithPreconfiguredValue', $result);
    }
}

<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for TranslateHelper
 */
final class TranslationHelperTest extends UnitTestCase
{
    #[Test]
    public function translateReturnsCorrectlyConfiguredTranslationParameterTokenWhenCalledWithLongArgumentList()
    {
        $mockTranslationParameterToken = $this->createMock(TranslationParameterToken::class);

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

        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->onlyMethods(['createTranslationParameterToken'])->getMock();
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn($mockTranslationParameterToken);


        $result = $mockTranslationHelper->translate('SomeId', 'SomeValue', ['a', 'couple', 'of', 'arguments'], 'SomeSource', 'Some.PackageKey', 42, 'SomeLocale');
        self::assertEquals('I am a translation result', $result);
    }

    #[Test]
    public function translateReturnsCorrectlyConfiguredTranslationParameterTokenWhenCalledWithShortHandString()
    {
        $mockTranslationParameterToken = $this->createMock(TranslationParameterToken::class);

        $mockTranslationParameterToken->expects($this->once())
            ->method('source', 'SomeSource')
            ->willReturn($mockTranslationParameterToken);

        $mockTranslationParameterToken->expects($this->once())
            ->method('package', 'Some.PackageKey')
            ->willReturn($mockTranslationParameterToken);

        $mockTranslationParameterToken->expects($this->once())
            ->method('translate')
            ->willReturn('I am a translation result');

        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->onlyMethods(['createTranslationParameterToken'])->getMock();
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn($mockTranslationParameterToken);

        $result = $mockTranslationHelper->translate('Some.PackageKey:SomeSource:SomeId');
        self::assertEquals('I am a translation result', $result);
    }

    #[Test]
    public function idReturnsTranslationParameterTokenWithPreconfiguredId()
    {
        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->onlyMethods(['createTranslationParameterToken'])->getMock();
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', 'SomeId')
            ->willReturn('TranslationParameterTokenWithPreconfiguredId');

        $result = $mockTranslationHelper->id('SomeId');
        self::assertEquals('TranslationParameterTokenWithPreconfiguredId', $result);
    }

    #[Test]
    public function valueReturnsTranslationParameterTokenWithPreconfiguredValue()
    {
        $mockTranslationHelper = $this->getMockBuilder(TranslationHelper::class)->onlyMethods(['createTranslationParameterToken'])->getMock();
        $mockTranslationHelper->expects(static::once())
            ->method('createTranslationParameterToken', null, 'SomeValue')
            ->willReturn('TranslationParameterTokenWithPreconfiguredValue');

        $result = $mockTranslationHelper->value('SomeValue');
        self::assertEquals('TranslationParameterTokenWithPreconfiguredValue', $result);
    }
}

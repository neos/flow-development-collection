<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Locale class
 */
final class LocaleTest extends UnitTestCase
{
    /**
     * Data provider for theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidLocaleIdentifiers(): \Iterator
    {
        yield [''];
        yield ['E'];
        yield ['deDE'];
    }

    #[DataProvider('invalidLocaleIdentifiers')]
    #[Test]
    public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers($invalidIdentifier)
    {
        $this->expectException(InvalidLocaleIdentifierException::class);
        new Locale($invalidIdentifier);
    }

    #[Test]
    public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers()
    {
        $locale = new Locale('de');
        self::assertEquals('de', $locale->getLanguage());
        self::assertNull($locale->getScript());
        self::assertNull($locale->getRegion());
        self::assertNull($locale->getVariant());

        $locale = new Locale('de_DE');
        self::assertEquals('de', $locale->getLanguage());
        self::assertEquals('DE', $locale->getRegion());
        self::assertNull($locale->getScript());
        self::assertNull($locale->getVariant());

        $locale = new Locale('en_Latn_US');
        self::assertEquals('en', $locale->getLanguage());
        self::assertEquals('Latn', $locale->getScript());
        self::assertEquals('US', $locale->getRegion());
        self::assertNull($locale->getVariant());

        $locale = new Locale('AR-arab_ae');
        self::assertEquals('ar', $locale->getLanguage());
        self::assertEquals('Arab', $locale->getScript());
        self::assertEquals('AE', $locale->getRegion());
        self::assertNull($locale->getVariant());
    }

    #[Test]
    public function producesCorrectLocaleIdentifierWhenStringCasted()
    {
        $locale = new Locale('de_DE');
        self::assertSame('de_DE', (string)$locale);

        $locale = new Locale('en_Latn_US');
        self::assertSame('en_Latn_US', (string)$locale);

        $locale = new Locale('AR-arab_ae');
        self::assertSame('ar_Arab_AE', (string)$locale);
    }
}

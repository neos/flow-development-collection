<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the Locale class
 */
class LocaleTest extends UnitTestCase
{
    /**
     * Data provider for theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers
     *
     * @return array
     */
    public function invalidLocaleIdentifiers()
    {
        return [
            [''],
            ['E'],
            ['deDE']
        ];
    }

    /**
     * @test
     * @dataProvider invalidLocaleIdentifiers
     */
    public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers($invalidIdentifier)
    {
        $this->expectException(I18n\Exception\InvalidLocaleIdentifierException::class);
        new I18n\Locale($invalidIdentifier);
    }

    /**
     * @test
     */
    public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers()
    {
        $locale = new I18n\Locale('de');
        self::assertEquals('de', $locale->getLanguage());
        self::assertNull($locale->getScript());
        self::assertNull($locale->getRegion());
        self::assertNull($locale->getVariant());

        $locale = new I18n\Locale('de_DE');
        self::assertEquals('de', $locale->getLanguage());
        self::assertEquals('DE', $locale->getRegion());
        self::assertNull($locale->getScript());
        self::assertNull($locale->getVariant());

        $locale = new I18n\Locale('en_Latn_US');
        self::assertEquals('en', $locale->getLanguage());
        self::assertEquals('Latn', $locale->getScript());
        self::assertEquals('US', $locale->getRegion());
        self::assertNull($locale->getVariant());

        $locale = new I18n\Locale('AR-arab_ae');
        self::assertEquals('ar', $locale->getLanguage());
        self::assertEquals('Arab', $locale->getScript());
        self::assertEquals('AE', $locale->getRegion());
        self::assertNull($locale->getVariant());
    }

    /**
     * @test
     */
    public function producesCorrectLocaleIdentifierWhenStringCasted()
    {
        $locale = new I18n\Locale('de_DE');
        self::assertEquals('de_DE', (string)$locale);

        $locale = new I18n\Locale('en_Latn_US');
        self::assertEquals('en_Latn_US', (string)$locale);

        $locale = new I18n\Locale('AR-arab_ae');
        self::assertEquals('ar_Arab_AE', (string)$locale);
    }
}

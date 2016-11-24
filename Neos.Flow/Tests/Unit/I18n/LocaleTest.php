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
     * @expectedException \Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException
     */
    public function theConstructorThrowsAnExceptionOnPassingAInvalidLocaleIdentifiers($invalidIdentifier)
    {
        new I18n\Locale($invalidIdentifier);
    }

    /**
     * @test
     */
    public function theConstructorRecognizesTheMostImportantValidLocaleIdentifiers()
    {
        $locale = new I18n\Locale('de');
        $this->assertEquals('de', $locale->getLanguage());
        $this->assertNull($locale->getScript());
        $this->assertNull($locale->getRegion());
        $this->assertNull($locale->getVariant());

        $locale = new I18n\Locale('de_DE');
        $this->assertEquals('de', $locale->getLanguage());
        $this->assertEquals('DE', $locale->getRegion());
        $this->assertNull($locale->getScript());
        $this->assertNull($locale->getVariant());

        $locale = new I18n\Locale('en_Latn_US');
        $this->assertEquals('en', $locale->getLanguage());
        $this->assertEquals('Latn', $locale->getScript());
        $this->assertEquals('US', $locale->getRegion());
        $this->assertNull($locale->getVariant());

        $locale = new I18n\Locale('AR-arab_ae');
        $this->assertEquals('ar', $locale->getLanguage());
        $this->assertEquals('Arab', $locale->getScript());
        $this->assertEquals('AE', $locale->getRegion());
        $this->assertNull($locale->getVariant());
    }

    /**
     * @test
     */
    public function producesCorrectLocaleIdentifierWhenStringCasted()
    {
        $locale = new I18n\Locale('de_DE');
        $this->assertEquals('de_DE', (string)$locale);

        $locale = new I18n\Locale('en_Latn_US');
        $this->assertEquals('en_Latn_US', (string)$locale);

        $locale = new I18n\Locale('AR-arab_ae');
        $this->assertEquals('ar_Arab_AE', (string)$locale);
    }
}

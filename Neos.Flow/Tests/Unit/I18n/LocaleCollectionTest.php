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

use Neos\Flow\I18n;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the LocaleCollection class
 */
class LocaleCollectionTest extends UnitTestCase
{
    /**
     * @var array<I18n\Locale>
     */
    protected $locales;

    /**
     * @var I18n\LocaleCollection
     */
    protected $localeCollection;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->locales = [
            new I18n\Locale('en'),
            new I18n\Locale('pl_PL'),
            new I18n\Locale('de'),
            new I18n\Locale('pl'),
        ];

        $this->localeCollection = new I18n\LocaleCollection();
    }

    /**
     * @test
     */
    public function localesAreAddedToTheCollectionCorrectlyWithHierarchyRelation()
    {
        foreach ($this->locales as $locale) {
            $this->localeCollection->addLocale($locale);
        }

        $this->assertEquals($this->locales[3], $this->localeCollection->getParentLocaleOf($this->locales[1]));
    }

    /**
     * @test
     */
    public function existingLocaleIsNotAddedToTheCollection()
    {
        $localeShouldBeAdded = $this->localeCollection->addLocale($this->locales[0]);
        $localeShouldNotBeAdded = $this->localeCollection->addLocale(new I18n\Locale('en'));
        $this->assertTrue($localeShouldBeAdded);
        $this->assertFalse($localeShouldNotBeAdded);
    }

    /**
     * @test
     */
    public function bestMatchingLocalesAreFoundCorrectly()
    {
        foreach ($this->locales as $locale) {
            $this->localeCollection->addLocale($locale);
        }

        $this->assertEquals($this->locales[1], $this->localeCollection->findBestMatchingLocale($this->locales[1]));
        $this->assertEquals($this->locales[1], $this->localeCollection->findBestMatchingLocale(new I18n\Locale('pl_PL_DVORAK')));
        $this->assertNull($this->localeCollection->findBestMatchingLocale(new I18n\Locale('sv')));
    }

    /**
     * @test
     */
    public function returnsNullWhenNoParentLocaleCouldBeFound()
    {
        foreach ($this->locales as $locale) {
            $this->localeCollection->addLocale($locale);
        }

        $this->assertNull($this->localeCollection->getParentLocaleOf(new I18n\Locale('sv')));
        $this->assertNull($this->localeCollection->getParentLocaleOf($this->locales[0]));
    }
}

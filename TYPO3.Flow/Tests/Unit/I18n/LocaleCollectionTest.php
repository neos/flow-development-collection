<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the LocaleCollection class
 *
 */
class LocaleCollectionTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var array<\TYPO3\Flow\I18n\Locale>
     */
    protected $locales;

    /**
     * @var \TYPO3\Flow\I18n\LocaleCollection
     */
    protected $localeCollection;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->locales = array(
            new \TYPO3\Flow\I18n\Locale('en'),
            new \TYPO3\Flow\I18n\Locale('pl_PL'),
            new \TYPO3\Flow\I18n\Locale('de'),
            new \TYPO3\Flow\I18n\Locale('pl'),
        );

        $this->localeCollection = new \TYPO3\Flow\I18n\LocaleCollection();
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
        $localeShouldNotBeAdded = $this->localeCollection->addLocale(new \TYPO3\Flow\I18n\Locale('en'));
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
        $this->assertEquals($this->locales[1], $this->localeCollection->findBestMatchingLocale(new \TYPO3\Flow\I18n\Locale('pl_PL_DVORAK')));
        $this->assertNull($this->localeCollection->findBestMatchingLocale(new \TYPO3\Flow\I18n\Locale('sv')));
    }

    /**
     * @test
     */
    public function returnsNullWhenNoParentLocaleCouldBeFound()
    {
        foreach ($this->locales as $locale) {
            $this->localeCollection->addLocale($locale);
        }

        $this->assertNull($this->localeCollection->getParentLocaleOf(new \TYPO3\Flow\I18n\Locale('sv')));
        $this->assertNull($this->localeCollection->getParentLocaleOf($this->locales[0]));
    }
}

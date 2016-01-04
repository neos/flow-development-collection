<?php
namespace TYPO3\Flow\Tests\Functional\I18n;

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
 * Testcase for the I18N placeholder replacing
 *
 */
class FormatResolverTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @var \TYPO3\Flow\I18n\FormatResolver
     */
    protected $formatResolver;

    /**
     * Initialize dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $this->formatResolver = $this->objectManager->get(\TYPO3\Flow\I18n\FormatResolver::class);
    }

    /**
     * @return array
     */
    public function placeholderAndDateValues()
    {
        $date = new \DateTime('@1322228231');
        return array(
            array('{0,datetime,date,short}', array($date), new \TYPO3\Flow\I18n\Locale('de'), '25.11.11'),
            array('{0,datetime,date,short}', array($date), new \TYPO3\Flow\I18n\Locale('en'), '11/25/11'),
            array('{0,datetime,time,full}', array($date), new \TYPO3\Flow\I18n\Locale('de'), '13:37:11 +00:00'),
            array('{0,datetime,dateTime,short}', array($date), new \TYPO3\Flow\I18n\Locale('en'), '11/25/11 1:37 p.m.')
        );
    }

    /**
     * @test
     * @dataProvider placeholderAndDateValues
     */
    public function formatResolverWithDatetimeReplacesCorrectValues($stringWithPlaceholders, $arguments, $locale, $expected)
    {
        $result = $this->formatResolver->resolvePlaceholders($stringWithPlaceholders, $arguments, $locale);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function formatResolverWorksCorrectlyForFullyQualifiedFormatterClassNames()
    {
        $actualFormatter = new \TYPO3\Flow\Tests\Functional\I18n\Fixtures\SampleFormatter;
        $locale = new \TYPO3\Flow\I18n\Locale('de');
        $testResult = $this->formatResolver->resolvePlaceholders('{0,TYPO3\Flow\Tests\Functional\I18n\Fixtures\SampleFormatter}', array('foo'), $locale);
        $this->assertEquals($actualFormatter->format('foo', $locale), $testResult);
    }
}

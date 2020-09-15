<?php
namespace Neos\Flow\Tests\Functional\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\FormatResolver;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the I18N placeholder replacing
 *
 */
class FormatResolverTest extends FunctionalTestCase
{
    /**
     * @var FormatResolver
     */
    protected $formatResolver;

    /**
     * Initialize dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $this->formatResolver = $this->objectManager->get(FormatResolver::class);
    }

    /**
     * @return array
     */
    public function placeholderAndDateValues()
    {
        $date = new \DateTime('@1322228231');
        return [
            ['{0,datetime,date,short}', [$date], new I18n\Locale('de'), '25.11.11'],
            ['{0,datetime,date,short}', [$date], new I18n\Locale('en'), '11/25/11'],
            ['{0,datetime,time,full}', [$date], new I18n\Locale('de'), '13:37:11 +00:00'],
            ['{0,datetime,dateTime,short}', [$date], new I18n\Locale('en'), '11/25/11 1:37 p.m.']
        ];
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
        $actualFormatter = new Fixtures\SampleFormatter;
        $locale = new I18n\Locale('de');
        $testResult = $this->formatResolver->resolvePlaceholders(sprintf('{0,%s}', Fixtures\SampleFormatter::class), ['foo'], $locale);
        $this->assertEquals($actualFormatter->format('foo', $locale), $testResult);
    }
}

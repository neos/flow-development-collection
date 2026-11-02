<?php

declare(strict_types=1);

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
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\Functional\I18n\Fixtures\SampleFormatter;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the I18N placeholder replacing
 *
 */
final class FormatResolverTest extends FunctionalTestCase
{
    protected FormatResolver $formatResolver;

    /**
     * Initialize dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formatResolver = $this->objectManager->get(FormatResolver::class);
    }

    public static function placeholderAndDateValues(): \Iterator
    {
        $date = new \DateTime('@1322228231');
        yield ['{0,datetime,date,short}', [$date], new Locale('de'), '25.11.11'];
        yield ['{0,datetime,date,short}', [$date], new Locale('en'), '11/25/11'];
        yield ['{0,datetime,time,full}', [$date], new Locale('de'), '13:37:11 +00:00'];
        yield ['{0,datetime,dateTime,short}', [$date], new Locale('en'), '11/25/11, 1:37 pm'];
    }

    #[DataProvider('placeholderAndDateValues')]
    #[Test]
    public function formatResolverWithDatetimeReplacesCorrectValues(string  $stringWithPlaceholders, array $arguments, Locale $locale, string $expected): void
    {
        $result = $this->formatResolver->resolvePlaceholders($stringWithPlaceholders, $arguments, $locale);
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function formatResolverWorksCorrectlyForFullyQualifiedFormatterClassNames(): void
    {
        $actualFormatter = new SampleFormatter();
        $locale = new Locale('de');
        $testResult = $this->formatResolver->resolvePlaceholders(sprintf('{0,%s}', SampleFormatter::class), ['foo'], $locale);
        self::assertEquals($actualFormatter->format('foo', $locale), $testResult);
    }
}

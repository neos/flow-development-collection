<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\I18n\Cldr\Reader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n\Cldr\Reader\NumbersReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class NumbersReaderTest extends FunctionalTestCase
{
    protected NumbersReader $numbersReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->numbersReader = $this->objectManager->get(NumbersReader::class);
    }


    public static function currencyFormatExampleDataProvider(): \Iterator
    {
        yield ['de', ['positivePrefix' => '', 'positiveSuffix' => " ¤", 'negativePrefix' => '-', 'negativeSuffix' => " ¤", 'multiplier' => 1, 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'minIntegerDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.0,]];
        yield ['en', ['positivePrefix' => '¤', 'positiveSuffix' => '', 'negativePrefix' => '-¤', 'negativeSuffix' => '', 'multiplier' => 1, 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'minIntegerDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.0,]];
    }


    #[DataProvider('currencyFormatExampleDataProvider')]
    #[Test]
    public function parseFormatFromCldr(string $localeName, array $expected): void
    {
        $locale = new Locale($localeName);
        $actual = $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_CURRENCY);
        self::assertEquals($expected, $actual);
    }

    public static function numberSystemDataProvider(): \Iterator
    {
        yield ['de', 'latn'];
        yield ['ar', 'arab'];
    }

    #[DataProvider('numberSystemDataProvider')]
    #[Test]
    public function getDefaultNumberingSystem(string $localeString, string $expected): void
    {
        self::assertSame($expected, $this->numbersReader->getDefaultNumberingSystem(new Locale($localeString)));
    }
}

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
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\I18n;

class NumbersReaderTest extends FunctionalTestCase
{

    /**
     * @var NumbersReader
     */
    protected $numbersReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->numbersReader = $this->objectManager->get(NumbersReader::class);
    }


    public function currencyFormatExampleDataProvider(): array
    {
        return [
            ['de', ['positivePrefix' => '', 'positiveSuffix' => " ¤", 'negativePrefix' => '-', 'negativeSuffix' => " ¤", 'multiplier' => 1, 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'minIntegerDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.0,]],
            ['en', ['positivePrefix' => '¤', 'positiveSuffix' => '', 'negativePrefix' => '-¤', 'negativeSuffix' => '', 'multiplier' => 1, 'minDecimalDigits' => 2, 'maxDecimalDigits' => 2, 'minIntegerDigits' => 1, 'primaryGroupingSize' => 3, 'secondaryGroupingSize' => 3, 'rounding' => 0.0,]],
        ];
    }


    /**
     * @test
     * @dataProvider currencyFormatExampleDataProvider
     *
     * @param string $localeName
     * @param string $expected
     * @throws I18n\Cldr\Reader\Exception\InvalidFormatLengthException
     * @throws I18n\Cldr\Reader\Exception\InvalidFormatTypeException
     * @throws I18n\Cldr\Reader\Exception\UnableToFindFormatException
     * @throws I18n\Cldr\Reader\Exception\UnsupportedNumberFormatException
     */
    public function parseFormatFromCldr(string $localeName, array $expected): void
    {
        $locale = new I18n\Locale($localeName);
        $actual = $this->numbersReader->parseFormatFromCldr($locale, NumbersReader::FORMAT_TYPE_CURRENCY);
        self::assertEquals($expected, $actual);
    }

    public function numberSystemDataProvider(): array
    {
        return [
            ['de', 'latn'],
            ['ar', 'arab'],
        ];
    }

    /**
     * @test
     * @dataProvider numberSystemDataProvider
     *
     * @param string $localeString
     * @param string $expected
     * @throws I18n\Exception\InvalidLocaleIdentifierException
     */
    public function getDefaultNumberingSystem(string $localeString, string $expected): void
    {
        self::assertEquals($expected, $this->numbersReader->getDefaultNumberingSystem(new I18n\Locale($localeString)));
    }
}

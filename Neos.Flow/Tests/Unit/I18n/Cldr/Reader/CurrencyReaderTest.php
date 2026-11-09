<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n\Cldr\Reader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n\Cldr\CldrModel;
use Neos\Flow\I18n\Cldr\CldrRepository;
use Neos\Flow\I18n\Cldr\Reader\CurrencyReader;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the CurrencyReader
 */
final class CurrencyReaderTest extends UnitTestCase
{
    /**
     * @var CurrencyReader
     */
    protected $reader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $sampleCurrencyFractionsData = [
            'fractions' => [
                'info[@iso4217="ADP"][@digits="0"][@rounding="0"]',
                'info[@iso4217="CHF"][@digits="2"][@rounding="5"]',
                'info[@iso4217="DEFAULT"][@digits="2"][@rounding="0"]',
            ],
        ];

        $mockModel = $this->getAccessibleMock(CldrModel::class, ['getRawArray'], [['fake/path']]);
        $mockModel->expects($this->once())->method('getRawArray')->with('currencyData')->willReturn(($sampleCurrencyFractionsData));

        $mockRepository = $this->createMock(CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModel')->with('supplemental/supplementalData')->willReturn(($mockModel));

        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('has')->with('fractions')->willReturn(false);
        $mockCache->expects($this->atLeastOnce())->method('set')->with('fractions');

        $this->reader = new CurrencyReader();
        $this->reader->injectCldrRepository($mockRepository);
        $this->reader->injectCache($mockCache);
        $this->reader->initializeObject();
    }

    /**
     * Data provider for returnsCorrectPluralForm
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function fractions(): \Iterator
    {
        yield ['ADP', 0, 0];
        yield ['CHF', 2, 5];
        yield ['EUR', 2, 0];
    }

    #[DataProvider('fractions')]
    #[Test]
    public function returnsCorrectFraction($currencyCode, $digits, $rounding)
    {
        $result = $this->reader->getFraction($currencyCode);
        self::assertSame($digits, $result['digits']);
        self::assertSame($rounding, $result['rounding']);
    }
}

<?php
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
use Neos\Flow\I18n\Cldr\Reader\CurrencyReader;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the CurrencyReader
 */
class CurrencyReaderTest extends UnitTestCase
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

        $mockModel = $this->getAccessibleMock(I18n\Cldr\CldrModel::class, ['getRawArray'], [['fake/path']]);
        $mockModel->expects(self::once())->method('getRawArray')->with('currencyData')->will(self::returnValue($sampleCurrencyFractionsData));

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects(self::once())->method('getModel')->with('supplemental/supplementalData')->will(self::returnValue($mockModel));

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects(self::atLeastOnce())->method('has')->with('fractions')->willReturn(false);
        $mockCache->expects(self::atLeastOnce())->method('set')->with('fractions');

        $this->reader = new CurrencyReader();
        $this->reader->injectCldrRepository($mockRepository);
        $this->reader->injectCache($mockCache);
        $this->reader->initializeObject();
    }

    /**
     * Data provider for returnsCorrectPluralForm
     *
     * @return array
     */
    public function fractions()
    {
        return [
            ['ADP', 0, 0],
            ['CHF', 2, 5],
            ['EUR', 2, 0]
        ];
    }

    /**
     * @test
     * @dataProvider fractions
     */
    public function returnsCorrectFraction($currencyCode, $digits, $rounding)
    {
        $result = $this->reader->getFraction($currencyCode);
        self::assertSame($digits, $result['digits']);
        self::assertSame($rounding, $result['rounding']);
    }
}

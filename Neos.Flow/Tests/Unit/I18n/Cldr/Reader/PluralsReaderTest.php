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
use Neos\Flow\I18n\Cldr\Reader\PluralsReader;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the PluralsReader
 */
final class PluralsReaderTest extends UnitTestCase
{
    /**
     * @var PluralsReader
     */
    protected $reader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $samplePluralRulesData = [
            'pluralRules[@locales="ro mo"]' => [
                'pluralRule[@count="one"]' => 'n is 1',
                'pluralRule[@count="few"]' => 'n is 0 OR n is not 1 AND n mod 100 in 1..19',
            ],
            'pluralRules[@locales="hr ru sr uk be bs sh"]' => [
                'pluralRule[@count="one"]' => 'n mod 10 is 1 and n mod 100 is not 11',
                'pluralRule[@count="few"]' => 'n mod 10 in 2..4 and n mod 100 not in 12..14',
                'pluralRule[@count="many"]' => 'n mod 10 is 0 or n mod 10 in 5..9 or n mod 100 in 11..14'
            ]
        ];

        $mockModel = $this->getAccessibleMock(CldrModel::class, ['getRawArray'], [['fake/path']]);
        $mockModel->expects($this->once())->method('getRawArray')->with('plurals')->willReturn(($samplePluralRulesData));

        $mockRepository = $this->createMock(CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModel')->with('supplemental/plurals')->willReturn(($mockModel));

        $mockCache = $this->createMock(VariableFrontend::class);
        $mockCache->expects($this->once())->method('has')->with('rulesets')->willReturn(false);
        $matcher = self::exactly(2);
        $mockCache->expects($matcher)->method('set')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('rulesets', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('rulesetsIndices', $parameters[0]);
            }
        });

        $this->reader = new PluralsReader();
        $this->reader->injectCldrRepository($mockRepository);
        $this->reader->injectCache($mockCache);
        $this->reader->initializeObject();
    }

    /**
     * Data provider for returnsCorrectPluralForm
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function quantities(): \Iterator
    {
        yield [
            'mo',
            [
                [1, PluralsReader::RULE_ONE],
                [2, PluralsReader::RULE_FEW],
                [100, PluralsReader::RULE_OTHER],
                [101, PluralsReader::RULE_FEW],
                [101.1, PluralsReader::RULE_OTHER]
            ]
        ];
        yield [
            'ru',
            [
                [1, PluralsReader::RULE_ONE],
                [2, PluralsReader::RULE_FEW],
                [11, PluralsReader::RULE_MANY],
                [100, PluralsReader::RULE_MANY],
                [101, PluralsReader::RULE_ONE],
                [101.1, PluralsReader::RULE_OTHER]
            ]
        ];
    }

    #[DataProvider('quantities')]
    #[Test]
    public function returnsCorrectPluralForm($localeName, $quantities)
    {
        $locale = new Locale($localeName);
        foreach ($quantities as $value) {
            list($quantity, $pluralForm) = $value;
            $result = $this->reader->getPluralForm($quantity, $locale);
            self::assertEquals($pluralForm, $result);
        }
    }
}

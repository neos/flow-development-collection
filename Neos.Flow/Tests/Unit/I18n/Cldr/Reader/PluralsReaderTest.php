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
use Neos\Flow\I18n\Cldr\Reader\PluralsReader;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the PluralsReader
 */
class PluralsReaderTest extends UnitTestCase
{
    /**
     * @var PluralsReader
     */
    protected $reader;

    /**
     * @return void
     */
    public function setUp()
    {
        $samplePluralRulesData = [
            'pluralRules[@locales="ro mo"]' => [
                'pluralRule[@count="one"]' => 'n is 1',
                'pluralRule[@count="few"]' => 'n is 0 OR n is not 1 AND n mod 100 in 1..19',
            ],
        ];

        $mockModel = $this->getAccessibleMock(I18n\Cldr\CldrModel::class, ['getRawArray'], [['fake/path']]);
        $mockModel->expects($this->once())->method('getRawArray')->with('plurals')->will($this->returnValue($samplePluralRulesData));

        $mockRepository = $this->createMock(I18n\Cldr\CldrRepository::class);
        $mockRepository->expects($this->once())->method('getModel')->with('supplemental/plurals')->will($this->returnValue($mockModel));

        $mockCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->at(0))->method('has')->with('rulesets')->will($this->returnValue(false));
        $mockCache->expects($this->at(1))->method('set')->with('rulesets');
        $mockCache->expects($this->at(2))->method('set')->with('rulesetsIndices');

        $this->reader = new PluralsReader();
        $this->reader->injectCldrRepository($mockRepository);
        $this->reader->injectCache($mockCache);
        $this->reader->initializeObject();
    }

    /**
     * Data provider for returnsCorrectPluralForm
     *
     * @return array
     */
    public function quantities()
    {
        return [
            [1, PluralsReader::RULE_ONE],
            [2, PluralsReader::RULE_FEW],
            [100, PluralsReader::RULE_OTHER],
            [101, PluralsReader::RULE_FEW],
            [101.1, PluralsReader::RULE_OTHER],
        ];
    }

    /**
     * @test
     * @dataProvider quantities
     */
    public function returnsCorrectPluralForm($quantity, $pluralForm)
    {
        $locale = new I18n\Locale('mo');

        $result = $this->reader->getPluralForm($quantity, $locale);
        $this->assertEquals($pluralForm, $result);
    }
}

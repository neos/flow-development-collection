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

use Neos\Flow\I18n\Cldr\Reader\PluralsReader;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\I18n;

class PluralsReaderTest extends FunctionalTestCase
{

    /**
     * @var PluralsReader
     */
    protected $pluralsReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluralsReader = $this->objectManager->get(PluralsReader::class);
    }

    /**
     * Data provider for returnsCorrectPluralForm
     *
     * @return array
     */
    public function quantities(): array
    {
        return [
            [
                'mo',
                [
                    [1, PluralsReader::RULE_ONE],
                    [2, PluralsReader::RULE_FEW],
                    [100, PluralsReader::RULE_OTHER],
                    [101, PluralsReader::RULE_FEW],
                    [101.1, PluralsReader::RULE_OTHER]
                ]
            ],
            [
                'ru',
                [
                    [1, PluralsReader::RULE_ONE],
                    [2, PluralsReader::RULE_FEW],
                    [11, PluralsReader::RULE_MANY],
                    [100, PluralsReader::RULE_MANY],
                    [101, PluralsReader::RULE_ONE],
                    [101.1, PluralsReader::RULE_OTHER]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider quantities
     * @param string $localeName
     * @param array $quantities
     * @throws I18n\Exception\InvalidLocaleIdentifierException
     */
    public function returnsCorrectPluralForm(string $localeName, array $quantities): void
    {
        $locale = new I18n\Locale($localeName);
        foreach ($quantities as $value) {
            list($quantity, $pluralForm) = $value;
            $result = $this->pluralsReader->getPluralForm($quantity, $locale);
            self::assertEquals($pluralForm, $result);
        }
    }
}

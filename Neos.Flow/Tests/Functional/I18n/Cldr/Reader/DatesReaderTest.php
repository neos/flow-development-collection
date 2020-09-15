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

use Neos\Flow\I18n\Cldr\Reader\DatesReader;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\I18n;

class DatesReaderTest extends FunctionalTestCase
{

    /**
     * @var DatesReader
     */
    protected $datesReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->datesReader = $this->objectManager->get(DatesReader::class);
    }

    /**
     * Data provider with valid format strings and expected results.
     *
     * @return array
     */
    public function formatStringsAndParsedFormats(): array
    {
        return [
            ['de',  ['dd', ['.'], 'MM', ['.'], 'y']],
            ['en',  ['MMM', [' '], 'd', [','], [' '], 'y']],
        ];
    }

    /**
     * @test
     * @dataProvider formatStringsAndParsedFormats
     * @param string $localeIdentifier
     * @param array $expectedResult
     * @throws I18n\Cldr\Reader\Exception\InvalidDateTimeFormatException
     * @throws I18n\Cldr\Reader\Exception\InvalidFormatLengthException
     * @throws I18n\Cldr\Reader\Exception\InvalidFormatTypeException
     * @throws I18n\Cldr\Reader\Exception\UnableToFindFormatException
     * @throws I18n\Exception\InvalidLocaleIdentifierException
     */
    public function formatStringsAreParsedCorrectly(string $localeIdentifier, array $expectedResult): void
    {
        $locale = new I18n\Locale($localeIdentifier);
        $result = $this->datesReader->parseFormatFromCldr($locale, DatesReader::FORMAT_TYPE_DATE, DatesReader::FORMAT_LENGTH_DEFAULT);
        self::assertEquals($expectedResult, $result);
    }
}

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
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\FunctionalTestCase;

class DatesReaderTest extends FunctionalTestCase
{

    /**
     * @var DatesReader
     */
    protected $datesReader;

    public function setUp(): void
    {
        parent::setUp();
        $this->datesReader = $this->objectManager->get(DatesReader::class);
    }

    /**
     * @test
     */
    public function parseFormatFromCldrCachesDateTimePatternsForEachLanguageIndependently(): void
    {
        $convertFormatToString = function (array $formatArray) {
            $format = '';
            array_walk_recursive($formatArray, function ($element) use (&$format) {
                $format .= $element;
            });
            return $format;
        };

        // Warms the cache with parsed formats for en_US and de
        $this->datesReader->parseFormatFromCldr(new Locale('en_US'), DatesReader::FORMAT_TYPE_DATETIME, DatesReader::FORMAT_LENGTH_SHORT);
        $this->datesReader->parseFormatFromCldr(new Locale('de'), DatesReader::FORMAT_TYPE_DATETIME, DatesReader::FORMAT_LENGTH_SHORT);

        // Reads two different cache entries
        $enUSFormat = $this->datesReader->parseFormatFromCldr(new Locale('en_US'), DatesReader::FORMAT_TYPE_DATETIME, DatesReader::FORMAT_LENGTH_SHORT);
        self::assertEquals('M/d/yy, h:mm a', $convertFormatToString($enUSFormat));

        $deFormat = $this->datesReader->parseFormatFromCldr(new Locale('de'), DatesReader::FORMAT_TYPE_DATETIME, DatesReader::FORMAT_LENGTH_SHORT);
        self::assertEquals('dd.MM.yy HH:mm', $convertFormatToString($deFormat));
    }
}

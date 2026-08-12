<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n\Cldr;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\I18n\Cldr\CldrParser;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the CldrParser
 *
 */
final class CldrParserTest extends UnitTestCase
{
    #[Test]
    public function parsesCldrDataCorrectly()
    {
        $sampleFilenamePath = __DIR__ . '/../Fixtures/MockCldrData.xml';
        $sampleParsedData = require(__DIR__ . '/../Fixtures/MockParsedCldrData.php');

        $parser = new CldrParser();

        $result = $parser->getParsedData($sampleFilenamePath);
        self::assertEquals($sampleParsedData, $result);
    }
}

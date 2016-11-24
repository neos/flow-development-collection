<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the CldrParser
 *
 */
class CldrParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parsesCldrDataCorrectly()
    {
        $sampleFilenamePath = __DIR__ . '/../Fixtures/MockCldrData.xml';
        $sampleParsedData = require(__DIR__ . '/../Fixtures/MockParsedCldrData.php');

        $parser = new I18n\Cldr\CldrParser();

        $result = $parser->getParsedData($sampleFilenamePath);
        $this->assertEquals($sampleParsedData, $result);
    }
}

<?php
namespace Neos\Flow\Tests\Unit\I18n\Xliff\V12;

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
 * Testcase for the XliffParser
 */
class XliffParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parsesXliffFileCorrectly()
    {
        $mockFilenamePath = __DIR__ . '/../../Fixtures/MockXliffData.xlf';
        $mockParsedData = require(__DIR__ . '/../../Fixtures/MockParsedXliffData.php');

        $parser = new I18n\Xliff\V12\XliffParser();
        $result = $parser->getParsedData($mockFilenamePath);
        self::assertEquals($mockParsedData, $result);
    }

    /**
     * @test
     */
    public function missingIdInSingularTransUnitCausesException()
    {
        $this->expectException(I18n\Xliff\Exception\InvalidXliffDataException::class);
        $mockFilenamePath = __DIR__ . '/../../Fixtures/MockInvalidXliffData.xlf';

        $parser = new I18n\Xliff\V12\XliffParser();
        $parser->getParsedData($mockFilenamePath);
    }

    /**
     * @test
     */
    public function missingIdInPluralTransUnitCausesException()
    {
        $this->expectException(I18n\Xliff\Exception\InvalidXliffDataException::class);
        $mockFilenamePath = __DIR__ . '/../../Fixtures/MockInvalidPluralXliffData.xlf';

        $parser = new I18n\Xliff\V12\XliffParser();
        $parser->getParsedData($mockFilenamePath);
    }
}

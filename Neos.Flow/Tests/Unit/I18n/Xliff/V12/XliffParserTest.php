<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\I18n\Xliff\V12\XliffParser;
use Neos\Flow\I18n\Xliff\Exception\InvalidXliffDataException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the XliffParser
 */
final class XliffParserTest extends UnitTestCase
{
    #[Test]
    public function parsesXliffFileCorrectly()
    {
        $mockFilenamePath = __DIR__ . '/../../Fixtures/MockXliffData.xlf';
        $mockParsedData = require(__DIR__ . '/../../Fixtures/MockParsedXliffData.php');

        $parser = new XliffParser();
        $result = $parser->getParsedData($mockFilenamePath);
        self::assertEquals($mockParsedData, $result);
    }

    #[Test]
    public function missingIdInSingularTransUnitCausesException()
    {
        $this->expectException(InvalidXliffDataException::class);
        $mockFilenamePath = __DIR__ . '/../../Fixtures/MockInvalidXliffData.xlf';

        $parser = new XliffParser();
        $parser->getParsedData($mockFilenamePath);
    }

    #[Test]
    public function missingIdInPluralTransUnitCausesException()
    {
        $this->expectException(InvalidXliffDataException::class);
        $mockFilenamePath = __DIR__ . '/../../Fixtures/MockInvalidPluralXliffData.xlf';

        $parser = new XliffParser();
        $parser->getParsedData($mockFilenamePath);
    }
}

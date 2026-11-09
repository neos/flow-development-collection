<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n\AbstractXmlParser;
use Neos\Flow\I18n\Exception\InvalidXmlFileException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the AbstractXmlParser class
 */
final class AbstractXmlParserTest extends UnitTestCase
{
    #[Test]
    public function invokesDoParsingFromRootMethodForActualParsing()
    {
        $sampleXmlFilePath = __DIR__ . '/Fixtures/MockCldrData.xml';

        $parser = $this->getAccessibleMock(AbstractXmlParser::class, ['doParsingFromRoot']);
        $parser->expects($this->once())->method('doParsingFromRoot');
        $parser->getParsedData($sampleXmlFilePath);
    }

    #[Test]
    public function throwsExceptionWhenBadFilenameGiven()
    {
        $this->expectException(InvalidXmlFileException::class);
        $mockFilenamePath = 'foo';

        $parser = $this->getAccessibleMock(AbstractXmlParser::class, ['doParsingFromRoot']);
        $parser->getParsedData($mockFilenamePath);
    }
}

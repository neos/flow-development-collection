<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\I18n;

/**
 * Testcase for the AbstractXmlParser class
 */
class AbstractXmlParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invokesDoParsingFromRootMethodForActualParsing()
    {
        $sampleXmlFilePath = __DIR__ . '/Fixtures/MockCldrData.xml';

        $parser = $this->getAccessibleMock(I18n\AbstractXmlParser::class, ['doParsingFromRoot']);
        $parser->expects($this->once())->method('doParsingFromRoot');
        $parser->getParsedData($sampleXmlFilePath);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\I18n\Exception\InvalidXmlFileException
     */
    public function throwsExceptionWhenBadFilenameGiven()
    {
        $mockFilenamePath = 'foo';

        $parser = $this->getAccessibleMock(I18n\AbstractXmlParser::class, ['doParsingFromRoot']);
        $parser->getParsedData($mockFilenamePath);
    }
}

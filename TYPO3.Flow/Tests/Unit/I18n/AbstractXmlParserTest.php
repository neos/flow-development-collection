<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the AbstractXmlParser class
 *
 */
class AbstractXmlParserTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function invokesDoParsingFromRootMethodForActualParsing()
    {
        $sampleXmlFilePath = __DIR__ . '/Fixtures/MockCldrData.xml';

        $parser = $this->getAccessibleMock('TYPO3\Flow\I18n\AbstractXmlParser', array('doParsingFromRoot'));
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

        $parser = $this->getAccessibleMock('TYPO3\Flow\I18n\AbstractXmlParser', array('doParsingFromRoot'));
        $parser->getParsedData($mockFilenamePath);
    }
}

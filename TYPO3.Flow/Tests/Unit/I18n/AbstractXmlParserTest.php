<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the AbstractXmlParser class
 *
 */
class AbstractXmlParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function invokesDoParsingFromRootMethodForActualParsing() {
		$sampleXmlFilePath = __DIR__ . '/Fixtures/MockCldrData.xml';

		$parser = $this->getAccessibleMock('TYPO3\Flow\I18n\AbstractXmlParser', array('doParsingFromRoot'));
		$parser->expects($this->once())->method('doParsingFromRoot');
		$parser->getParsedData($sampleXmlFilePath);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Exception\InvalidXmlFileException
	 */
	public function throwsExceptionWhenBadFilenameGiven() {
		$mockFilenamePath = 'foo';

		$parser = $this->getAccessibleMock('TYPO3\Flow\I18n\AbstractXmlParser', array('doParsingFromRoot'));
		$parser->getParsedData($mockFilenamePath);
	}
}

?>
<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Xliff;

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
 * Testcase for the XliffParser
 *
 */
class XliffParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function parsesXliffFileCorrectly() {
		$mockFilenamePath = __DIR__ . '/../Fixtures/MockXliffData.xlf';
		$mockParsedData = require(__DIR__ . '/../Fixtures/MockParsedXliffData.php');

		$parser = new \TYPO3\Flow\I18n\Xliff\XliffParser();
		$result = $parser->getParsedData($mockFilenamePath);
		$this->assertEquals($mockParsedData, $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Xliff\Exception\InvalidXliffDataException
	 */
	public function missingIdInSingularTransUnitCausesException() {
		$mockFilenamePath = __DIR__ . '/../Fixtures/MockInvalidXliffData.xlf';

		$parser = new \TYPO3\Flow\I18n\Xliff\XliffParser();
		$parser->getParsedData($mockFilenamePath);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Xliff\Exception\InvalidXliffDataException
	 */
	public function missingIdInPluralTransUnitCausesException() {
		$mockFilenamePath = __DIR__ . '/../Fixtures/MockInvalidPluralXliffData.xlf';

		$parser = new \TYPO3\Flow\I18n\Xliff\XliffParser();
		$parser->getParsedData($mockFilenamePath);
	}

}

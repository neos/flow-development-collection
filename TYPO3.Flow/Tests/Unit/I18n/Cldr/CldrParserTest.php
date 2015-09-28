<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Cldr;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the CldrParser
 *
 */
class CldrParserTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function parsesCldrDataCorrectly()
    {
        $sampleFilenamePath = __DIR__ . '/../Fixtures/MockCldrData.xml';
        $sampleParsedData = require(__DIR__ . '/../Fixtures/MockParsedCldrData.php');

        $parser = new \TYPO3\Flow\I18n\Cldr\CldrParser();

        $result = $parser->getParsedData($sampleFilenamePath);
        $this->assertEquals($sampleParsedData, $result);
    }
}

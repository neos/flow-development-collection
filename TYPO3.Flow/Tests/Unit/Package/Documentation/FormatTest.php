<?php
namespace TYPO3\Flow\Tests\Unit\Package\Documentation;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the documentation format class
 *
 */
class FormatTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Sets up this test case
     *
     */
    protected function setUp()
    {
        vfsStream::setup('testDirectory');
    }

    /**
     * @test
     */
    public function constructSetsNameAndPathToFormat()
    {
        $documentationPath = vfsStream::url('testDirectory') . '/';

        $format = new \TYPO3\Flow\Package\Documentation\Format('DocBook', $documentationPath);

        $this->assertEquals('DocBook', $format->getFormatName());
        $this->assertEquals($documentationPath, $format->getFormatPath());
    }

    /**
     * @test
     */
    public function getLanguagesScansFormatDirectoryAndReturnsLanguagesAsStrings()
    {
        $formatPath = vfsStream::url('testDirectory') . '/';

        \TYPO3\Flow\Utility\Files::createDirectoryRecursively($formatPath . 'en');

        $format = new \TYPO3\Flow\Package\Documentation\Format('DocBook', $formatPath);
        $availableLanguages = $format->getAvailableLanguages();

        $this->assertEquals(array('en'), $availableLanguages);
    }
}

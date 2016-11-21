<?php
namespace TYPO3\Flow\Tests\Unit\Package\Documentation;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Package\Documentation;
use TYPO3\Flow\Utility\Files;

/**
 * Testcase for the documentation format class
 */
class FormatTest extends UnitTestCase
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

        $format = new Documentation\Format('DocBook', $documentationPath);

        $this->assertEquals('DocBook', $format->getFormatName());
        $this->assertEquals($documentationPath, $format->getFormatPath());
    }

    /**
     * @test
     */
    public function getLanguagesScansFormatDirectoryAndReturnsLanguagesAsStrings()
    {
        $formatPath = vfsStream::url('testDirectory') . '/';

        Files::createDirectoryRecursively($formatPath . 'en');

        $format = new Documentation\Format('DocBook', $formatPath);
        $availableLanguages = $format->getAvailableLanguages();

        $this->assertEquals(['en'], $availableLanguages);
    }
}

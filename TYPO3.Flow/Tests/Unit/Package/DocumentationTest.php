<?php
namespace TYPO3\Flow\Tests\Unit\Package;

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
use TYPO3\Flow\Package;
use TYPO3\Flow\Utility\Files;

/**
 * Testcase for the package documentation class
 */
class DocumentationTest extends UnitTestCase
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
    public function constructSetsPackageNameAndPathToDocumentation()
    {
        $documentationPath = vfsStream::url('testDirectory') . '/';

        $mockPackage = $this->createMock(Package\PackageInterface::class);

        $documentation = new Package\Documentation($mockPackage, 'Manual', $documentationPath);

        $this->assertSame($mockPackage, $documentation->getPackage());
        $this->assertEquals('Manual', $documentation->getDocumentationName());
        $this->assertEquals($documentationPath, $documentation->getDocumentationPath());
    }

    /**
     * @test
     */
    public function getDocumentationFormatsScansDocumentationDirectoryAndReturnsDocumentationFormatObjectsIndexedByFormatName()
    {
        $documentationPath = vfsStream::url('testDirectory') . '/';

        $mockPackage = $this->createMock(Package\PackageInterface::class);

        Files::createDirectoryRecursively($documentationPath . 'DocBook/en');

        $documentation = new Package\Documentation($mockPackage, 'Manual', $documentationPath);
        $documentationFormats = $documentation->getDocumentationFormats();

        $this->assertEquals('DocBook', $documentationFormats['DocBook']->getFormatName());
    }
}

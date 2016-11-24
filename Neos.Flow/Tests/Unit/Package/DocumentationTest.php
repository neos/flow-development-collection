<?php
namespace Neos\Flow\Tests\Unit\Package;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Package;
use Neos\Utility\Files;

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

<?php
namespace Neos\Flow\Tests\Unit\I18n\Xliff;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\LoggerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the FileAdapter
 */
class FileAdapterTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $mockParsedXliffFile;

    /**
     * @return void
     */
    public function setUp()
    {
        $mockParsedXliffData = require(__DIR__ . '/../../Fixtures/MockParsedXliffData.php');
        $this->mockParsedXliffFile = $mockParsedXliffData[0];
        $this->mockParsedXliffFile['fileIdentifier'] = 'Neos.Flow:Foo';
    }

    /**
     * @test
     */
    public function targetIsReturnedCorrectlyWhenSourceProvided()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, new I18n\Locale('de'));

        $result = $fileAdapter->getTargetBySource('Source string');
        $this->assertEquals('Übersetzte Zeichenkette', $result);

        $result = $fileAdapter->getTargetBySource('Source singular', 0);
        $this->assertEquals('Übersetzte Einzahl', $result);

        $result = $fileAdapter->getTargetBySource('Source singular', 2);
        $this->assertEquals('Übersetzte Mehrzahl 2', $result);

        $result = $fileAdapter->getTargetBySource('Not existing label');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function targetIsReturnedCorrectlyWhenIdProvided()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, new I18n\Locale('de'));

        $result = $fileAdapter->getTargetByTransUnitId('key1');
        $this->assertEquals('Übersetzte Zeichenkette', $result);

        $result = $fileAdapter->getTargetByTransUnitId('key2', 1);
        $this->assertEquals('Übersetzte Mehrzahl 1', $result);

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($fileAdapter, 'i18nLogger', $mockLogger);

        $result = $fileAdapter->getTargetByTransUnitId('not.existing');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function sourceIsReturnedWhenIdProvidedAndSourceEqualsTargetLanguage()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, new I18n\Locale('en_US'));

        $result = $fileAdapter->getTargetByTransUnitId('key3');
        $this->assertEquals('No target', $result);
    }

    /**
     * @test
     */
    public function getTargetBySourceLogsSilentlyIfNoTransUnitsArePresent()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter([
            'fileIdentifier' => 'Neos.Flow:Foo'
        ], new I18n\Locale('de'));

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('log')
            ->with($this->stringStartsWith('No trans-unit elements were found'), LOG_DEBUG);
        $this->inject($fileAdapter, 'i18nLogger', $mockLogger);

        $fileAdapter->getTargetBySource('foo');
    }
}

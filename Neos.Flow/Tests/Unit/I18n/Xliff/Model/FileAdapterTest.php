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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;
use Psr\Log\LoggerInterface;

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
    protected function setUp(): void
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
        self::assertEquals('Übersetzte Zeichenkette', $result);

        $result = $fileAdapter->getTargetBySource('Source singular', 0);
        self::assertEquals('Übersetzte Einzahl', $result);

        $result = $fileAdapter->getTargetBySource('Source singular', 2);
        self::assertEquals('Übersetzte Mehrzahl 2', $result);

        $result = $fileAdapter->getTargetBySource('Not existing label');
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function targetIsReturnedCorrectlyWhenIdProvided()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, new I18n\Locale('de'));

        $result = $fileAdapter->getTargetByTransUnitId('key1');
        self::assertEquals('Übersetzte Zeichenkette', $result);

        $result = $fileAdapter->getTargetByTransUnitId('key2', 1);
        self::assertEquals('Übersetzte Mehrzahl 1', $result);

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($fileAdapter, 'i18nLogger', $mockLogger);

        $result = $fileAdapter->getTargetByTransUnitId('not.existing');
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function sourceIsReturnedWhenIdProvidedAndSourceEqualsTargetLanguage()
    {
        $fileAdapter = new I18n\Xliff\Model\FileAdapter($this->mockParsedXliffFile, new I18n\Locale('en_US'));

        $result = $fileAdapter->getTargetByTransUnitId('key3');
        self::assertEquals('No target', $result);
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
        $mockLogger->expects(self::once())
            ->method('debug')
            ->with($this->stringStartsWith('No trans-unit elements were found'));
        $this->inject($fileAdapter, 'i18nLogger', $mockLogger);

        $fileAdapter->getTargetBySource('foo');
    }
}

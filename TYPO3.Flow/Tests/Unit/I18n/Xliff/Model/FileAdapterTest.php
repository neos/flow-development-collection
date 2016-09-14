<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Xliff;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use \TYPO3\Flow\I18n;
use \TYPO3\Flow\Log;

/**
 * Testcase for the FileAdapter
 */
class FileAdapterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var I18n\Xliff\Model\FileAdapter
     */
    protected $model;


    /**
     * @return void
     */
    public function setUp()
    {
        $mockFileData = require(__DIR__ . '/../../Fixtures/MockMergedXliffData.php');

        $this->model = new I18n\Xliff\Model\FileAdapter($mockFileData, new I18n\Locale('de'));
        $this->inject($this->model, 'i18nLogger', $this->createMock(Log\LoggerInterface::class));
    }

    /**
     * @test
     */
    public function targetIsReturnedCorrectlyWhenSourceProvided()
    {
        $result = $this->model->getTargetBySource('Source string');
        $this->assertEquals('Übersetzte Zeichenkette', $result);

        $result = $this->model->getTargetBySource('Source singular', 0);
        $this->assertEquals('Übersetzte Einzahl', $result);

        $result = $this->model->getTargetBySource('Source singular', 2);
        $this->assertEquals('Übersetzte Mehrzahl 2', $result);

        $result = $this->model->getTargetBySource('Not existing label');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function targetIsReturnedCorrectlyWhenIdProvided()
    {
        $result = $this->model->getTargetByTransUnitId('key1');
        $this->assertEquals('Übersetzte Zeichenkette', $result);

        $result = $this->model->getTargetByTransUnitId('key2', 1);
        $this->assertEquals('Übersetzte Mehrzahl 1', $result);

        $result = $this->model->getTargetByTransUnitId('not.existing');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function sourceIsReturnedWhenIdProvidedAndSourceEqualsTargetLanguage()
    {
        $mockFileData = require(__DIR__ . '/../../Fixtures/MockMergedXliffData.php');

        $this->model = new I18n\Xliff\Model\FileAdapter($mockFileData, new I18n\Locale('en_US'));

        $result = $this->model->getTargetByTransUnitId('key3');
        $this->assertEquals('No target', $result);
    }

    /**
     * @test
     */
    public function getTargetBySourceLogsSilentlyIfNoTransUnitsArePresent()
    {
        $mockLogger = $this->createMock(Log\LoggerInterface::class);
        $mockLogger->expects($this->once())->method('log')->with($this->stringStartsWith('No trans-unit elements were found'), LOG_DEBUG);

        $this->model = new I18n\Xliff\Model\FileAdapter(['fileIdentifier' => 'foo'], new I18n\Locale('de'));

        $this->inject($this->model, 'i18nLogger', $mockLogger);

        $this->model->getTargetBySource('foo');
    }
}

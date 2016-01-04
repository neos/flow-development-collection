<?php
namespace TYPO3\Flow\Tests\Unit\Monitor;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Utility\Files;

/**
 * Testcase for the File Monitor class
 */
class FileMonitorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $unixStylePath;

    /**
     * @var string
     */
    protected $unixStylePathAndFilename;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        $this->unixStylePath = Files::getUnixStylePath(__DIR__);
        $this->unixStylePathAndFilename = Files::getUnixStylePath(__FILE__);

        vfsStream::setup('testDirectory');
    }

    /**
     * @test
     */
    public function monitorFileRegistersAFileForMonitoring()
    {
        $monitor = new \TYPO3\Flow\Monitor\FileMonitor('Flow_Test');
        $monitor->monitorFile(__FILE__);
        $this->assertSame(array($this->unixStylePathAndFilename), $monitor->getMonitoredFiles());
    }

    /**
     * @test
     */
    public function aFileAppearsOnlyOnceInTheListOfMonitoredFiles()
    {
        $monitor = new \TYPO3\Flow\Monitor\FileMonitor('Flow_Test');
        $monitor->monitorFile(__FILE__);
        $monitor->monitorFile(__FILE__);
        $this->assertSame(array($this->unixStylePathAndFilename), $monitor->getMonitoredFiles());
    }

    /**
     * @test
     */
    public function monitorDirectoryRegistersAWholeDirectoryForMonitoring()
    {
        $monitor = new \TYPO3\Flow\Monitor\FileMonitor('Flow_Test');
        $monitor->monitorDirectory(__DIR__);
        $this->assertSame(array(Files::getNormalizedPath($this->unixStylePath)), $monitor->getMonitoredDirectories());
    }

    /**
     * @test
     */
    public function aDirectoryAppearsOnlyOnceInTheListOfMonitoredDirectories()
    {
        $monitor = new \TYPO3\Flow\Monitor\FileMonitor('Flow_Test');
        $monitor->monitorDirectory(__DIR__);
        $monitor->monitorDirectory(__DIR__ . '/');
        $this->assertSame(array(Files::getNormalizedPath($this->unixStylePath)), $monitor->getMonitoredDirectories());
    }

    /**
     * @test
     */
    public function detectChangesDetectsChangesInMonitoredFiles()
    {
        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockMonitor = $this->getMock(\TYPO3\Flow\Monitor\FileMonitor::class, array('loadDetectedDirectoriesAndFiles', 'detectChangedFiles'), array('Flow_Test'), '', true, true);
        $mockMonitor->expects($this->once())->method('detectChangedFiles')->with(array($this->unixStylePathAndFilename))->will($this->returnValue(array()));

        $mockMonitor->injectSystemLogger($mockSystemLogger);
        $mockMonitor->monitorFile(__FILE__);

        $mockMonitor->detectChanges();
    }

    /**
     * @test
     */
    public function detectChangesEmitsFilesHaveChangedSignalIfFilesHaveChanged()
    {
        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $monitoredFiles = array(__FILE__ . '1', __FILE__ . '2', __FILE__ . '3');

        $expectedChangedFiles = array();
        $expectedChangedFiles[$this->unixStylePathAndFilename . '1'] = ChangeDetectionStrategyInterface::STATUS_CREATED;
        $expectedChangedFiles[$this->unixStylePathAndFilename . '3'] = ChangeDetectionStrategyInterface::STATUS_DELETED;

        $mockMonitor = $this->getAccessibleMock(\TYPO3\Flow\Monitor\FileMonitor::class, array('loadDetectedDirectoriesAndFiles', 'detectChangedFiles', 'emitFilesHaveChanged'), array('Flow_Test'), '', true, true);
        $mockMonitor->expects($this->once())->method('detectChangedFiles')->with($monitoredFiles)->will($this->returnValue($expectedChangedFiles));
        $mockMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('Flow_Test', $expectedChangedFiles);

        $mockMonitor->injectSystemLogger($mockSystemLogger);
        $mockMonitor->_set('monitoredFiles', $monitoredFiles);

        $mockMonitor->detectChanges();
    }

    /**
     * @test
     */
    public function detectChangedFilesFetchesTheStatusOfGivenFilesAndReturnsAListOfChangeFilesAndTheirStatus()
    {
        $mockStrategy = $this->getMock(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::class);
        $mockStrategy->expects($this->exactly(2))->method('getFileStatus')->will($this->onConsecutiveCalls(ChangeDetectionStrategyInterface::STATUS_CREATED, ChangeDetectionStrategyInterface::STATUS_UNCHANGED));

        $mockMonitor = $this->getAccessibleMock(\TYPO3\Flow\Monitor\FileMonitor::class, array('dummy'), array('Flow_Test'), '', true, true);
        $mockMonitor->injectChangeDetectionStrategy($mockStrategy);
        $result = $mockMonitor->_call('detectChangedFiles', array(__FILE__ . '1', __FILE__ . '2'));

        $this->assertEquals(array(__FILE__ . '1' => ChangeDetectionStrategyInterface::STATUS_CREATED), $result);
    }

    /**
     * @test
     */
    public function detectChangesDetectsChangesInFilesOfMonitoredDirectoriesIfPatternIsMatched()
    {
        $testPath = vfsStream::url('testDirectory');

        // Initially known files per path
        $knownDirectoriesAndFiles = array(
            Files::getNormalizedPath($testPath) => array(
                $testPath . '/NodeTypes.foo.yaml' => 1
            )
        );

        file_put_contents($testPath . '/NodeTypes.foo.yaml', '');

        // Outcome of the change dection per file
        $changeDetectionResult = array(
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        );

        // Expected emitted changes for files
        $expectedEmittedChanges = array(
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        );

        $fileMonitor = $this->setUpFileMonitorForDetection($changeDetectionResult, $expectedEmittedChanges, $knownDirectoriesAndFiles);
        $fileMonitor->monitorDirectory($testPath, 'NodeTypes(\..+)?\.yaml');
        $fileMonitor->detectChanges();
    }

    /**
     * @test
     */
    public function detectChangesDetectsCreatedFilesOfMonitoredDirectoriesOnlyIfPatternIsMatched()
    {
        $testPath = vfsStream::url('testDirectory');

        // Initially known files per path
        $knownDirectoriesAndFiles = array(
            Files::getNormalizedPath($testPath) => array(
                $testPath . '/NodeTypes.foo.yaml' => 1
            )
        );

        // Create some new files
        file_put_contents($testPath . '/test.txt', '');
        file_put_contents($testPath . '/NodeTypes.yaml', '');

        // Outcome of the change dection per file
        $changeDetectionResult = array(
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED,
            $testPath . '/NodeTypes.yaml' => ChangeDetectionStrategyInterface::STATUS_CREATED
        );

        // Expected emitted changes for files
        $expectedEmittedChanges = array(
            $testPath . '/NodeTypes.yaml' => ChangeDetectionStrategyInterface::STATUS_CREATED,
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_DELETED
        );

        $fileMonitor = $this->setUpFileMonitorForDetection($changeDetectionResult, $expectedEmittedChanges, $knownDirectoriesAndFiles);
        $fileMonitor->monitorDirectory($testPath, 'NodeTypes(\..+)?\.yaml');
        $fileMonitor->detectChanges();
    }

    /**
     * @test
     */
    public function detectChangesDetectsDeletedFilesOfMonitoredDirectoriesIfPatternIsMatched()
    {
        $testPath = vfsStream::url('testDirectory');

        // Initially known files per path
        $knownDirectoriesAndFiles = array(
            Files::getNormalizedPath($testPath) => array(
                $testPath . '/NodeTypes.foo.yaml' => 1
            )
        );

        // Outcome of the change dection per file
        $changeDetectionResult = array(
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_DELETED
        );

        // Expected emitted changes for files
        $expectedEmittedChanges = array(
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_DELETED
        );

        $fileMonitor = $this->setUpFileMonitorForDetection($changeDetectionResult, $expectedEmittedChanges, $knownDirectoriesAndFiles);
        $fileMonitor->monitorDirectory($testPath, 'NodeTypes(\..+)?\.yaml');
        $fileMonitor->detectChanges();
    }

    /**
     * @test
     */
    public function detectChangesAddsCreatedFilesOfMonitoredDirectoriesToStoredDirectories()
    {
        $testPath = vfsStream::url('testDirectory');

        // Initially known files per path
        $knownDirectoriesAndFiles = array(
        );

        // Create a new file
        file_put_contents($testPath . '/test.txt', '');

        // Outcome of the change dection per file
        $changeDetectionResult = array(
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        );

        // Expected emitted changes for files
        $expectedEmittedChanges = array(
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        );

        $fileMonitor = $this->setUpFileMonitorForDetection($changeDetectionResult, $expectedEmittedChanges, $knownDirectoriesAndFiles);
        $fileMonitor->monitorDirectory($testPath);
        $fileMonitor->detectChanges();

        $this->assertEquals(array(
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        ), $fileMonitor->_get('changedFiles'));
        $this->assertCount(1, $fileMonitor->_get('changedPaths'));
    }

    /**
     * @param array $changeDetectionResult
     * @param array $expectedEmittedChanges
     * @param array $knownDirectoriesAndFiles
     * @return \TYPO3\Flow\Monitor\FileMonitor
     */
    protected function setUpFileMonitorForDetection(array $changeDetectionResult, array $expectedEmittedChanges, array $knownDirectoriesAndFiles)
    {
        $mockChangeDetectionStrategy = $this->getMock(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::class);
        $mockChangeDetectionStrategy->expects($this->any())->method('getFileStatus')->will($this->returnCallback(function ($pathAndFilename) use ($changeDetectionResult) {
            if (isset($changeDetectionResult[$pathAndFilename])) {
                return $changeDetectionResult[$pathAndFilename];
            } else {
                return ChangeDetectionStrategyInterface::STATUS_UNCHANGED;
            }
        }));

        $fileMonitor = $this->getAccessibleMock(\TYPO3\Flow\Monitor\FileMonitor::class, array('emitFilesHaveChanged', 'emitDirectoriesHaveChanged'), array('Flow_Test'), '', true, true);
        $this->inject($fileMonitor, 'changeDetectionStrategy', $mockChangeDetectionStrategy);
        $fileMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('Flow_Test', $expectedEmittedChanges);

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);
        $fileMonitor->injectSystemLogger($mockSystemLogger);

        $mockCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array(), array(), '', false);
        $mockCache->expects($this->once())->method('get')->will($this->returnValue(json_encode($knownDirectoriesAndFiles)));
        $fileMonitor->injectCache($mockCache);

        return $fileMonitor;
    }
}

<?php
namespace Neos\Flow\Tests\Unit\Monitor;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use org\bovigo\vfs\vfsStream;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;
use Neos\Cache;

/**
 * Testcase for the File Monitor class
 */
class FileMonitorTest extends UnitTestCase
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
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorFile(__FILE__);
        $this->assertSame([$this->unixStylePathAndFilename], $monitor->getMonitoredFiles());
    }

    /**
     * @test
     */
    public function aFileAppearsOnlyOnceInTheListOfMonitoredFiles()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorFile(__FILE__);
        $monitor->monitorFile(__FILE__);
        $this->assertSame([$this->unixStylePathAndFilename], $monitor->getMonitoredFiles());
    }

    /**
     * @test
     */
    public function monitorDirectoryRegistersAWholeDirectoryForMonitoring()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorDirectory(__DIR__);
        $this->assertSame([Files::getNormalizedPath($this->unixStylePath)], $monitor->getMonitoredDirectories());
    }

    /**
     * @test
     */
    public function aDirectoryAppearsOnlyOnceInTheListOfMonitoredDirectories()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorDirectory(__DIR__);
        $monitor->monitorDirectory(__DIR__ . '/');
        $this->assertSame([Files::getNormalizedPath($this->unixStylePath)], $monitor->getMonitoredDirectories());
    }

    /**
     * @test
     */
    public function detectChangesDetectsChangesInMonitoredFiles()
    {
        $mockSystemLogger = $this->createMock(SystemLoggerInterface::class);

        $mockMonitor = $this->getMockBuilder(FileMonitor::class)->setMethods(['loadDetectedDirectoriesAndFiles', 'detectChangedFiles'])->setConstructorArgs(['Flow_Test'])->getMock();
        $mockMonitor->expects($this->once())->method('detectChangedFiles')->with([$this->unixStylePathAndFilename])->will($this->returnValue([]));

        $mockMonitor->injectSystemLogger($mockSystemLogger);
        $mockMonitor->monitorFile(__FILE__);

        $mockMonitor->detectChanges();
    }

    /**
     * @test
     */
    public function detectChangesEmitsFilesHaveChangedSignalIfFilesHaveChanged()
    {
        $mockSystemLogger = $this->createMock(SystemLoggerInterface::class);

        $monitoredFiles = [__FILE__ . '1', __FILE__ . '2', __FILE__ . '3'];

        $expectedChangedFiles = [];
        $expectedChangedFiles[$this->unixStylePathAndFilename . '1'] = ChangeDetectionStrategyInterface::STATUS_CREATED;
        $expectedChangedFiles[$this->unixStylePathAndFilename . '3'] = ChangeDetectionStrategyInterface::STATUS_DELETED;

        $mockMonitor = $this->getAccessibleMock(FileMonitor::class, ['loadDetectedDirectoriesAndFiles', 'detectChangedFiles', 'emitFilesHaveChanged'], ['Flow_Test'], '', true, true);
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
        $mockStrategy = $this->createMock(\Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::class);
        $mockStrategy->expects($this->exactly(2))->method('getFileStatus')->will($this->onConsecutiveCalls(ChangeDetectionStrategyInterface::STATUS_CREATED, ChangeDetectionStrategyInterface::STATUS_UNCHANGED));

        $mockMonitor = $this->getAccessibleMock(FileMonitor::class, ['dummy'], ['Flow_Test'], '', true, true);
        $mockMonitor->injectChangeDetectionStrategy($mockStrategy);
        $result = $mockMonitor->_call('detectChangedFiles', [__FILE__ . '1', __FILE__ . '2']);

        $this->assertEquals([__FILE__ . '1' => ChangeDetectionStrategyInterface::STATUS_CREATED], $result);
    }

    /**
     * @test
     */
    public function detectChangesDetectsChangesInFilesOfMonitoredDirectoriesIfPatternIsMatched()
    {
        $testPath = vfsStream::url('testDirectory');

        // Initially known files per path
        $knownDirectoriesAndFiles = [
            Files::getNormalizedPath($testPath) => [
                $testPath . '/NodeTypes.foo.yaml' => 1
            ]
        ];

        file_put_contents($testPath . '/NodeTypes.foo.yaml', '');

        // Outcome of the change dection per file
        $changeDetectionResult = [
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ];

        // Expected emitted changes for files
        $expectedEmittedChanges = [
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_CHANGED
        ];

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
        $knownDirectoriesAndFiles = [
            Files::getNormalizedPath($testPath) => [
                $testPath . '/NodeTypes.foo.yaml' => 1
            ]
        ];

        // Create some new files
        file_put_contents($testPath . '/test.txt', '');
        file_put_contents($testPath . '/NodeTypes.yaml', '');

        // Outcome of the change dection per file
        $changeDetectionResult = [
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED,
            $testPath . '/NodeTypes.yaml' => ChangeDetectionStrategyInterface::STATUS_CREATED
        ];

        // Expected emitted changes for files
        $expectedEmittedChanges = [
            $testPath . '/NodeTypes.yaml' => ChangeDetectionStrategyInterface::STATUS_CREATED,
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_DELETED
        ];

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
        $knownDirectoriesAndFiles = [
            Files::getNormalizedPath($testPath) => [
                $testPath . '/NodeTypes.foo.yaml' => 1
            ]
        ];

        // Outcome of the change dection per file
        $changeDetectionResult = [
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_DELETED
        ];

        // Expected emitted changes for files
        $expectedEmittedChanges = [
            $testPath . '/NodeTypes.foo.yaml' => ChangeDetectionStrategyInterface::STATUS_DELETED
        ];

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
        $knownDirectoriesAndFiles = [
        ];

        // Create a new file
        file_put_contents($testPath . '/test.txt', '');

        // Outcome of the change dection per file
        $changeDetectionResult = [
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        ];

        // Expected emitted changes for files
        $expectedEmittedChanges = [
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        ];

        $fileMonitor = $this->setUpFileMonitorForDetection($changeDetectionResult, $expectedEmittedChanges, $knownDirectoriesAndFiles);
        $fileMonitor->monitorDirectory($testPath);
        $fileMonitor->detectChanges();

        $this->assertEquals([
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        ], $fileMonitor->_get('changedFiles'));
        $this->assertCount(1, $fileMonitor->_get('changedPaths'));
    }

    /**
     * @param array $changeDetectionResult
     * @param array $expectedEmittedChanges
     * @param array $knownDirectoriesAndFiles
     * @return FileMonitor
     */
    protected function setUpFileMonitorForDetection(array $changeDetectionResult, array $expectedEmittedChanges, array $knownDirectoriesAndFiles)
    {
        $mockChangeDetectionStrategy = $this->createMock(ChangeDetectionStrategyInterface::class);
        $mockChangeDetectionStrategy->expects($this->any())->method('getFileStatus')->will($this->returnCallback(function ($pathAndFilename) use ($changeDetectionResult) {
            if (isset($changeDetectionResult[$pathAndFilename])) {
                return $changeDetectionResult[$pathAndFilename];
            } else {
                return ChangeDetectionStrategyInterface::STATUS_UNCHANGED;
            }
        }));

        $fileMonitor = $this->getAccessibleMock(FileMonitor::class, ['emitFilesHaveChanged', 'emitDirectoriesHaveChanged'], ['Flow_Test'], '', true, true);
        $this->inject($fileMonitor, 'changeDetectionStrategy', $mockChangeDetectionStrategy);
        $fileMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('Flow_Test', $expectedEmittedChanges);

        $mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
        $fileMonitor->injectSystemLogger($mockSystemLogger);

        $mockCache = $this->getMockBuilder(Cache\Frontend\StringFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->once())->method('get')->will($this->returnValue(json_encode($knownDirectoriesAndFiles)));
        $fileMonitor->injectCache($mockCache);

        return $fileMonitor;
    }
}

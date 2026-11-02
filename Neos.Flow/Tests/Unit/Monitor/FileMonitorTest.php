<?php

declare(strict_types=1);

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
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the File Monitor class
 */
final class FileMonitorTest extends UnitTestCase
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
    protected function setUp(): void
    {
        $this->unixStylePath = Files::getUnixStylePath(__DIR__);
        $this->unixStylePathAndFilename = Files::getUnixStylePath(__FILE__);

        vfsStream::setup('testDirectory');
    }

    #[Test]
    public function monitorFileRegistersAFileForMonitoring()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorFile(__FILE__);
        self::assertSame([$this->unixStylePathAndFilename], $monitor->getMonitoredFiles());
    }

    #[Test]
    public function aFileAppearsOnlyOnceInTheListOfMonitoredFiles()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorFile(__FILE__);
        $monitor->monitorFile(__FILE__);
        self::assertSame([$this->unixStylePathAndFilename], $monitor->getMonitoredFiles());
    }

    #[Test]
    public function monitorDirectoryRegistersAWholeDirectoryForMonitoring()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorDirectory(__DIR__);
        self::assertSame([Files::getNormalizedPath($this->unixStylePath)], $monitor->getMonitoredDirectories());
    }

    #[Test]
    public function aDirectoryAppearsOnlyOnceInTheListOfMonitoredDirectories()
    {
        $monitor = new FileMonitor('Flow_Test');
        $monitor->monitorDirectory(__DIR__);
        $monitor->monitorDirectory(__DIR__ . '/');
        self::assertSame([Files::getNormalizedPath($this->unixStylePath)], $monitor->getMonitoredDirectories());
    }

    #[Test]
    public function detectChangesDetectsChangesInMonitoredFiles()
    {
        $mockSystemLogger = $this->createStub(LoggerInterface::class);

        $mockMonitor = $this->getMockBuilder(FileMonitor::class)->onlyMethods(['loadDetectedDirectoriesAndFiles', 'detectChangedFiles'])->setConstructorArgs(['Flow_Test'])->getMock();
        $mockMonitor->expects($this->once())->method('detectChangedFiles')->with([$this->unixStylePathAndFilename])->willReturn(([]));

        $mockMonitor->injectLogger($mockSystemLogger);
        $mockMonitor->monitorFile(__FILE__);

        $mockMonitor->detectChanges();
    }

    #[Test]
    public function detectChangesEmitsFilesHaveChangedSignalIfFilesHaveChanged()
    {
        $mockSystemLogger = $this->createStub(LoggerInterface::class);

        $monitoredFiles = [__FILE__ . '1', __FILE__ . '2', __FILE__ . '3'];

        $expectedChangedFiles = [];
        $expectedChangedFiles[$this->unixStylePathAndFilename . '1'] = ChangeDetectionStrategyInterface::STATUS_CREATED;
        $expectedChangedFiles[$this->unixStylePathAndFilename . '3'] = ChangeDetectionStrategyInterface::STATUS_DELETED;

        $mockMonitor = $this->getAccessibleMock(FileMonitor::class, ['loadDetectedDirectoriesAndFiles', 'detectChangedFiles', 'emitFilesHaveChanged'], ['Flow_Test'], '', true, true);
        $mockMonitor->expects($this->once())->method('detectChangedFiles')->with($monitoredFiles)->willReturn(($expectedChangedFiles));
        $mockMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('Flow_Test', $expectedChangedFiles);


        $mockMonitor->injectLogger($mockSystemLogger);
        $mockMonitor->_set('monitoredFiles', $monitoredFiles);

        $mockMonitor->detectChanges();
    }

    #[Test]
    public function detectChangedFilesFetchesTheStatusOfGivenFilesAndReturnsAListOfChangeFilesAndTheirStatus()
    {
        $mockStrategy = $this->createMock(ChangeDetectionStrategyInterface::class);
        $mockStrategy->expects($this->exactly(2))->method('getFileStatus')->willReturnOnConsecutiveCalls(ChangeDetectionStrategyInterface::STATUS_CREATED, ChangeDetectionStrategyInterface::STATUS_UNCHANGED);

        $mockMonitor = $this->getAccessibleMock(FileMonitor::class, [], ['Flow_Test'], '', true, true);
        $mockMonitor->injectChangeDetectionStrategy($mockStrategy);
        $result = $mockMonitor->_call('detectChangedFiles', [__FILE__ . '1', __FILE__ . '2']);

        self::assertEquals([__FILE__ . '1' => ChangeDetectionStrategyInterface::STATUS_CREATED], $result);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

        self::assertEquals([
            $testPath . '/test.txt' => ChangeDetectionStrategyInterface::STATUS_CREATED
        ], $fileMonitor->_get('changedFiles'));
        self::assertCount(1, $fileMonitor->_get('changedPaths'));
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
        $mockChangeDetectionStrategy->method('getFileStatus')->willReturnCallback(function ($pathAndFilename) use ($changeDetectionResult) {
            if (isset($changeDetectionResult[$pathAndFilename])) {
                return $changeDetectionResult[$pathAndFilename];
            } else {
                return ChangeDetectionStrategyInterface::STATUS_UNCHANGED;
            }
        });

        $fileMonitor = $this->getAccessibleMock(FileMonitor::class, ['emitFilesHaveChanged', 'emitDirectoriesHaveChanged'], ['Flow_Test'], '', true, true);
        $this->inject($fileMonitor, 'changeDetectionStrategy', $mockChangeDetectionStrategy);
        $fileMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('Flow_Test', $expectedEmittedChanges);

        $mockSystemLogger = $this->createMock(LoggerInterface::class);
        $fileMonitor->injectLogger($mockSystemLogger);

        $mockCache = $this->createMock(StringFrontend::class);
        $mockCache->expects($this->once())->method('get')->willReturn((json_encode($knownDirectoriesAndFiles)));
        $fileMonitor->injectCache($mockCache);

        return $fileMonitor;
    }
}

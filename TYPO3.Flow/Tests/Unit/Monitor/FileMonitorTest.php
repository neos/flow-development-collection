<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Monitor;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Testcase for the File Monitor class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FileMonitorTest extends \F3\Testing\BaseTestCase {

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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->unixStylePath = \F3\FLOW3\Utility\Files::getUnixStylePath(__DIR__);
		$this->unixStylePathAndFilename = \F3\FLOW3\Utility\Files::getUnixStylePath(__FILE__);

		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function fileMonitorCachesTheListOfKnownDirectoriesAndFiles() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('has')->with('directoriesAndFiles')->will($this->returnValue(TRUE));
		$mockCache->expects($this->once())->method('get')->with('directoriesAndFiles')->will($this->returnValue(array('foo' => 'bar')));
		$mockCache->expects($this->once())->method('set')->with('directoriesAndFiles', array('baz' => 'quux'));

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('dummy'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->injectCache($mockCache);
		$mockMonitor->initializeObject();

		$this->assertSame(array('foo' => 'bar'), $mockMonitor->_get('directoriesAndFiles'));

		$mockMonitor->_set('directoriesAndFiles', array('baz' => 'quux'));
		$mockMonitor->_set('directoriesChanged', TRUE);

		$mockMonitor->shutdownObject();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function monitorFileRegistersAFileForMonitoring() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorFile(__FILE__);
		$this->assertSame(array($this->unixStylePathAndFilename), $monitor->getMonitoredFiles());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aFileAppearsOnlyOnceInTheListOfMonitoredFiles() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorFile(__FILE__);
		$monitor->monitorFile(__FILE__);
		$this->assertSame(array($this->unixStylePathAndFilename), $monitor->getMonitoredFiles());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function monitorDirectoryRegistersAWholeDirectoryForMonitoring() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorDirectory(__DIR__);
		$this->assertSame(array($this->unixStylePath), $monitor->getMonitoredDirectories());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aDirectoryAppearsOnlyOnceInTheListOfMonitoredDirectories() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorDirectory(__DIR__);
		$monitor->monitorDirectory(__DIR__ . '/');
		$this->assertSame(array($this->unixStylePath), $monitor->getMonitoredDirectories());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesDetectsChangesInMonitoredFiles() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockMonitor = $this->getMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('detectChangedFiles')->with(array($this->unixStylePathAndFilename))->will($this->returnValue(array()));

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->monitorFile(__FILE__);

		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesEmitsFilesHaveChangedSignalIfFilesHaveChanged() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$monitoredFiles = array(__FILE__ . '1', __FILE__ . '2', __FILE__ . '3');

		$expectedChangedFiles = array();
		$expectedChangedFiles[$this->unixStylePathAndFilename . '1'] = \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED;
		$expectedChangedFiles[$this->unixStylePathAndFilename . '3'] = \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED;

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles', 'emitFilesHaveChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('detectChangedFiles')->with($monitoredFiles)->will($this->returnValue($expectedChangedFiles));
		$mockMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('FLOW3_Test', $expectedChangedFiles);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->_set('monitoredFiles', $monitoredFiles);

		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesDetectsChangesInFilesOfMonitoredDirectories() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');
		$testPath = \vfsStream::url('testDirectory');

		$knownDirectoriesAndFiles = array(
			$testPath => array(
				$testPath . '/oldfile.txt',
				$testPath . '/newfile.txt'
			)
		);

		$expectedChangedFiles = array($testPath . '/newfile.txt');

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles', 'emitFilesHaveChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->at(0))->method('detectChangedFiles')->with(array())->will($this->returnValue(array()));
		$mockMonitor->expects($this->at(1))->method('detectChangedFiles')->with($knownDirectoriesAndFiles[$testPath])->will($this->returnValue($expectedChangedFiles));
		$mockMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('FLOW3_Test', $expectedChangedFiles);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->_set('directoriesAndFiles', $knownDirectoriesAndFiles);

		$mockMonitor->monitorDirectory($testPath);
		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesDetectsNewlyCreatedFilesInMonitoredDirectories() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$testPath = \vfsStream::url('testDirectory');
		file_put_contents($testPath . '/oldfile.txt', 'void');
		file_put_contents($testPath . '/newfile.txt', 'void');

		$knownDirectoriesAndFiles = array(
			$testPath => array($testPath . '/oldfile.txt')
		);

		$actualDirectoriesAndFiles = array(
			$testPath => array(
				$testPath . '/oldfile.txt',
				$testPath . '/newfile.txt'
			)
		);

		$expectedChangedFiles = array($testPath . '/newfile.txt');

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles', 'emitFilesHaveChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->at(0))->method('detectChangedFiles')->with(array())->will($this->returnValue(array()));
		$mockMonitor->expects($this->at(1))->method('detectChangedFiles')->with($actualDirectoriesAndFiles[$testPath])->will($this->returnValue($expectedChangedFiles));
		$mockMonitor->expects($this->once())->method('emitFilesHaveChanged')->with('FLOW3_Test', $expectedChangedFiles);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->_set('directoriesAndFiles', $knownDirectoriesAndFiles);

		$mockMonitor->monitorDirectory($testPath);
		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesEmitsDirectoryChangedSignalAndMemorizesDirectoryIfDirectoryHasNotBeenMonitoredPreviously() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$expectedChangedDirectories = array($this->unixStylePath => \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED);

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles', 'emitDirectoriesHaveChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->any())->method('detectChangedFiles')->will($this->returnValue(array()));
		$mockMonitor->expects($this->once())->method('emitDirectoriesHaveChanged')->with('FLOW3_Test', $expectedChangedDirectories);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->monitorDirectory(__DIR__);

		$mockMonitor->detectChanges();

		$directoriesAndFiles = $mockMonitor->_get('directoriesAndFiles');
		$this->assertTrue(array_search($this->unixStylePathAndFilename, $directoriesAndFiles[$this->unixStylePath]) !== FALSE);
		$this->assertTrue($mockMonitor->_get('directoriesChanged'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesEmitsDirectoryChangedSignalIfDirectoryHasBeenRemoved() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$expectedChangedDirectories = array(\vfsStream::url('testDirectory') . '/bar' => \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED);

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles', 'emitDirectoriesHaveChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->any())->method('detectChangedFiles')->will($this->returnValue(array()));
		$mockMonitor->expects($this->once())->method('emitDirectoriesHaveChanged')->with('FLOW3_Test', $expectedChangedDirectories);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->_set('directoriesAndFiles', array(\vfsStream::url('testDirectory') . '/bar' => array()));

		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangedFilesFetchesTheStatusOfGivenFilesAndReturnsAListOfChangeFilesAndTheirStatus() {
		$mockStrategy = $this->getMock('F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface');
		$mockStrategy->expects($this->at(0))->method('getFileStatus')->with(__FILE__ . '1')->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED));
		$mockStrategy->expects($this->at(1))->method('getFileStatus')->with(__FILE__ . '2')->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED));

		$mockMonitor = $this->getAccessibleMock('F3\FLOW3\Monitor\FileMonitor', array('dummy'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->injectChangeDetectionStrategy($mockStrategy);
		$result = $mockMonitor->_call('detectChangedFiles', array(__FILE__ . '1', __FILE__ . '2'));

		$this->assertSame(array(__FILE__ . '1' => \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED), $result);
	}
}
?>
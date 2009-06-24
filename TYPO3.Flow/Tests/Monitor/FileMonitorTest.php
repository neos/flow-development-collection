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
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 */

/**
 * Testcase for the File Monitor class
 *
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FileMonitorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function monitorFileRegistersAFileForMonitoring() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorFile(__FILE__);
		$this->assertSame(array(__FILE__), $monitor->getMonitoredFiles());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aFileAppearsOnlyOnceInTheListOfMonitoredFiles() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorFile(__FILE__);
		$monitor->monitorFile(__FILE__);
		$this->assertSame(array(__FILE__), $monitor->getMonitoredFiles());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function monitorDirectoryRegistersAWholeDirectoryForMonitoring() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorDirectory(__DIR__);
		$this->assertSame(array(__DIR__), $monitor->getMonitoredDirectories());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aDirectoryAppearsOnlyOnceInTheListOfMonitoredDirectories() {
		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->monitorDirectory(__DIR__);
		$monitor->monitorDirectory(__DIR__ . '/');
		$this->assertSame(array(__DIR__), $monitor->getMonitoredDirectories());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesDetectsChangesInMonitoredFiles() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockMonitor = $this->getMock('F3\FLOW3\Monitor\FileMonitor', array('detectChangedFiles'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('detectChangedFiles')->with(array(__FILE__));

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->monitorFile(__FILE__);

		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesDetectsChangesInFilesOfMonitoredDirectories() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockMonitor = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Monitor\FileMonitor'), array('detectChangedFiles', 'emitDirectoryHasChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->at(0))->method('detectChangedFiles')->with(array());
		$mockMonitor->expects($this->at(1))->method('detectChangedFiles')->with(array(__FILE__));

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->_set('directoriesAndFiles', array(__DIR__ => array(__FILE__)));
		$mockMonitor->monitorDirectory(__DIR__);

		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesEmitsDirectoryChangedSignalAndMemorizesDirectoryIfDirectoryHasNotBeenMonitoredPreviously() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockMonitor = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Monitor\FileMonitor'), array('detectChangedFiles', 'emitDirectoryHasChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('emitDirectoryHasChanged')->with('FLOW3_Test', __DIR__, \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->monitorDirectory(__DIR__);

		$mockMonitor->detectChanges();

		$directoriesAndFiles = $mockMonitor->_get('directoriesAndFiles');
		$this->assertTrue(array_search(__FILE__, $directoriesAndFiles[__DIR__]) !== FALSE);
		$this->assertTrue($mockMonitor->_get('directoriesChanged'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesEmitsDirectoryChangedSignalIfDirectoryHasBeenRemoved() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockMonitor = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Monitor\FileMonitor'), array('detectChangedFiles', 'emitDirectoryHasChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('emitDirectoryHasChanged')->with('FLOW3_Test', '/foo/bar', \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED);

		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->_set('directoriesAndFiles', array('/foo/bar' => array()));

		$mockMonitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangedFilesFetchesTheStatusOfGivenFilesAndEmitsSignalsForEachChangedFile() {
		$mockStrategy = $this->getMock('F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface');
		$mockStrategy->expects($this->at(0))->method('getFileStatus')->with(__FILE__ . '1')->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED));
		$mockStrategy->expects($this->at(1))->method('getFileStatus')->with(__FILE__ . '2')->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED));

		$mockMonitor = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Monitor\FileMonitor'), array('emitFileHasChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('emitFileHasChanged')->with('FLOW3_Test', __FILE__ . '1', \F3\FLOW3\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED);

		$mockMonitor->injectChangeDetectionStrategy($mockStrategy);
		$result = $mockMonitor->_call('detectChangedFiles', array(__FILE__ . '1', __FILE__ . '2'));

		$this->assertSame(1, $result);
	}
}
?>
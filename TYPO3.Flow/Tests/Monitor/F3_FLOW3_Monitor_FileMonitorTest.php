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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
	public function detectChangesCallsTheGetFileStatusAndGetDirectoryStatusMethodsOfAnInjectedChangeDetectionStrategy() {
		$mockStrategy = $this->getMock('F3\FLOW3\Monitor\ChangeDetectionStrategyInterface');
		$mockStrategy->expects($this->once())->method('getFileStatus')->with(__FILE__)->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_UNCHANGED));
		$mockStrategy->expects($this->once())->method('getDirectoryStatus')->with(__DIR__)->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_UNCHANGED));

		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_Test');
		$monitor->injectChangeDetectionStrategy($mockStrategy);
		$monitor->monitorFile(__FILE__);
		$monitor->monitorDirectory(__DIR__);

		$monitor->detectChanges();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectChangesEmitsASignalIfItDetectsAFileOrDirectoryChange() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockStrategy = $this->getMock('F3\FLOW3\Monitor\ChangeDetectionStrategyInterface');
		$mockStrategy->expects($this->once())->method('getFileStatus')->with(__FILE__)->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_CHANGED));
		$mockStrategy->expects($this->once())->method('getDirectoryStatus')->with(__DIR__)->will($this->returnValue(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_CHANGED));

		$mockMonitor = $this->getMock('F3\FLOW3\Monitor\FileMonitor', array('emitFileHasChanged', 'emitDirectoryHasChanged'), array('FLOW3_Test'), '', TRUE, TRUE);
		$mockMonitor->expects($this->once())->method('emitFileHasChanged')->with('FLOW3_Test', __FILE__, \F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_CHANGED);
		$mockMonitor->expects($this->once())->method('emitDirectoryHasChanged')->with('FLOW3_Test', __DIR__, \F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_CHANGED);

		$mockMonitor->injectChangeDetectionStrategy($mockStrategy);
		$mockMonitor->injectSystemLogger($mockSystemLogger);
		$mockMonitor->monitorFile(__FILE__);
		$mockMonitor->monitorDirectory(__DIR__);

		$mockMonitor->detectChanges();
	}
}
?>
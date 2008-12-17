<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\SignalSlot;

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
 * @subpackage Tests
 * @version $Id$
 */


/**
 * Testcase for the Signal Aspect
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class SignalAspectTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardSignalToDispatcherForwardsTheSignalsMethodArgumentsToTheDispatcher() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPoint', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getClassName')->will($this->returnValue('SampleClass'));
		$mockJoinPoint->expects($this->any())->method('getMethodName')->will($this->returnValue('emitSignal'));
		$mockJoinPoint->expects($this->any())->method('getMethodArguments')->will($this->returnValue(array('arg1' => 'val1', 'arg2' => array('val2'))));

		$mockDispatcher = $this->getMock('F3\FLOW3\SignalSlot\Dispatcher');
		$mockDispatcher->expects($this->once())->method('dispatch')->with('SampleClass', 'emitSignal', array('arg1' => 'val1', 'arg2' => array('val2')));

		$aspect = new \F3\FLOW3\SignalSlot\SignalAspect();
		$aspect->injectDispatcher($mockDispatcher);
		$aspect->forwardSignalToDispatcher($mockJoinPoint);
	}
}
?>
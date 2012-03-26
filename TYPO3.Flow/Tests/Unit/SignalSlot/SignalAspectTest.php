<?php
namespace TYPO3\FLOW3\Tests\Unit\SignalSlot;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Testcase for the Signal Aspect
 *
 */
class SignalAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function forwardSignalToDispatcherForwardsTheSignalsMethodArgumentsToTheDispatcher() {
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPoint', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getClassName')->will($this->returnValue('SampleClass'));
		$mockJoinPoint->expects($this->any())->method('getMethodName')->will($this->returnValue('emitSignal'));
		$mockJoinPoint->expects($this->any())->method('getMethodArguments')->will($this->returnValue(array('arg1' => 'val1', 'arg2' => array('val2'))));

		$mockDispatcher = $this->getMock('TYPO3\FLOW3\SignalSlot\Dispatcher');
		$mockDispatcher->expects($this->once())->method('dispatch')->with('SampleClass', 'signal', array('arg1' => 'val1', 'arg2' => array('val2')));

		$aspect = $this->getAccessibleMock('TYPO3\FLOW3\SignalSlot\SignalAspect', array('dummy'));
		$aspect->_set('dispatcher', $mockDispatcher);
		$aspect->forwardSignalToDispatcher($mockJoinPoint);
	}
}
?>
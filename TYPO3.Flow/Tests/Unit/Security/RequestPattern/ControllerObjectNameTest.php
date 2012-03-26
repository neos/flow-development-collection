<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\RequestPattern;

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
 * Testcase for the controller object name request pattern
 *
 */
class ControllerObjectNameTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function requestMatchingBasicallyWorks() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\FLOW3\Security\Controller\LoginController'));

		$requestPattern = new \TYPO3\FLOW3\Security\RequestPattern\ControllerObjectName();
		$requestPattern->setPattern('TYPO3\FLOW3\Security\.*');

		$this->assertTrue($requestPattern->matchRequest($request));
		$this->assertEquals('TYPO3\FLOW3\Security\.*', $requestPattern->getPattern());
	}
}
?>
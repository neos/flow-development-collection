<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
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
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function requestMatchingBasicallyWorks() {
		$request = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\FLOW3\Security\Controller\LoginController'));

		$requestPattern = new \TYPO3\FLOW3\Security\RequestPattern\ControllerObjectName();
		$requestPattern->setPattern('TYPO3\FLOW3\Security\.*');

		$this->assertTrue($requestPattern->matchRequest($request));
	}
}
?>
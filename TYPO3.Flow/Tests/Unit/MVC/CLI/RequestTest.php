<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\CLI;

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

use \TYPO3\FLOW3\MVC\CLI\Request;

/**
 * Testcase for the CLI Request class
 */
class RequestTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation() {
		$request = new Request();
		$request->setControllerObjectName('TYPO3\FLOW3\Command\CacheCommandController');
		$request->setControllerCommandName('flush');

		$command = $request->getCommand();
		$this->assertEquals('typo3.flow3:cache:flush', $command->getCommandIdentifier());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectNameAndSetControllerCommandNameUnsetTheBuiltCommandObject() {
		$request = new Request();
		$request->setControllerObjectName('TYPO3\FLOW3\Command\CacheCommandController');
		$request->setControllerCommandName('flush');
		$request->getCommand();

		$request->setControllerObjectName('TYPO3\FLOW3\Command\BeerCommandController');
		$request->setControllerCommandName('drink');

		$command = $request->getCommand();
		$this->assertEquals('typo3.flow3:beer:drink', $command->getCommandIdentifier());
	}
}
?>
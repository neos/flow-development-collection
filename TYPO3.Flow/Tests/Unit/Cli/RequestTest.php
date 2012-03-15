<?php
namespace TYPO3\FLOW3\Tests\Unit\Cli;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Cli\Request;

/**
 * Testcase for the CLI Request class
 */
class RequestTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
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
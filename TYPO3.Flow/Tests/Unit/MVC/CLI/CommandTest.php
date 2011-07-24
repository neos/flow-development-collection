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

use \TYPO3\FLOW3\MVC\CLI\Command;

/**
 * Testcase for the CLI Command class
 */
class CommandTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function commandIdentifiers() {
		return array(
			array('TYPO3\FLOW3\Command\CacheCommandController', 'flush', 'typo3.flow3:cache:flush'),
			array('RobertLemke\Foo\Faa\Fuuum\Command\CoffeeCommandController', 'brew', 'robertlemke.foo.faa.fuuum:coffee:brew'),
			array('SomePackage\Command\CookieCommandController', 'bake', 'somepackage:cookie:bake')
		);
	}

	/**
	 * @test
	 * @dataProvider commandIdentifiers
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier) {
		$command = new Command($controllerClassName, $commandName);
		$this->assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
	}
}
?>
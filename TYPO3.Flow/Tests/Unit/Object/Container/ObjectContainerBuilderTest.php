<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Object\Container;

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

require_once(__DIR__ . '/../Fixture/ClassWithInitializeObjectMethod.php');

/**
 * Testcase for the dynamic object container
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectContainerBuilderTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildLifecycleInitializationCommandContainsTheCorrectParameters() {
		$mockObjectContainer = $this->getAccessibleMock('F3\FLOW3\Object\Container\ObjectContainerBuilder', array('dummy'));
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Tests\Object\Fixture\ClassWithInitializeObjectMethod', 'F3\FLOW3\Tests\Object\Fixture\ClassWithInitializeObjectMethod');

		$generatedPhpCode = $mockObjectContainer->_call('buildLifecycleInitializationCommand', $objectConfiguration, \F3\FLOW3\Object\Container\ObjectContainerInterface::INITIALIZATIONCAUSE_CREATED);
		$this->assertContains('$o->initializeObject(' . \F3\FLOW3\Object\Container\ObjectContainerInterface::INITIALIZATIONCAUSE_CREATED . ')', $generatedPhpCode);
	}

}
?>
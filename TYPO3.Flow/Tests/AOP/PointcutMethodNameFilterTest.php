<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 */

require_once('Fixture/MethodsTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Method Name Filter
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PointcutMethodNameFilterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenMethodName() {
		$className = 'F3\FLOW3\Tests\AOP\Fixture\MethodsTaggedWithSomething';

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$methodNameFilter = new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('someMethod');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodNameFilter->matches($className, 'someMethod', $className, 1));

		$methodNameFilter = new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('some.*');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodNameFilter->matches($className, 'someMethod', $className, 1));
		$this->assertTrue($methodNameFilter->matches($className, 'someOtherMethod', $className, 2));

		$methodNameFilter = new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('.*Method');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($methodNameFilter->matches($className, 'somethingCompletelyDifferent', $className, 1));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesIgnoresFinalMethodsEvenIfTheirNameMatches() {
		$className = uniqid('TestClass');
		eval("
			class $className {
				final public function someFinalMethod() {}
			}"
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$methodNameFilter = new \F3\FLOW3\AOP\PointcutMethodNameFilter('someFinalMethod');
		$methodNameFilter->injectReflectionService($mockReflectionService);

		$this->assertFalse($methodNameFilter->matches($className, 'someFinalMethod', $className, 1));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTakesTheVisibilityModifierIntoAccountIfOneWasSpecified() {
		$className = uniqid('TestClass');
		eval("
			class $className {
				public function somePublicMethod() {}
				protected function someProtectedMethod() {}
				private function somePrivateMethod() {}
			}"
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$methodNameFilter = new \F3\FLOW3\AOP\PointcutMethodNameFilter('some.*', 'public');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));

		$methodNameFilter = new \F3\FLOW3\AOP\PointcutMethodNameFilter('some.*', 'protected');
		$methodNameFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePublicMethod', $className, 1));
		$this->assertTrue($methodNameFilter->matches(__CLASS__, 'someProtectedMethod', $className, 1));
		$this->assertFalse($methodNameFilter->matches(__CLASS__, 'somePrivateMethod', $className, 1));
	}
}
?>
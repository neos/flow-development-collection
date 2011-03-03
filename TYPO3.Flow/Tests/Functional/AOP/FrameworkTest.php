<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\AOP;

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
 * Testcase for the AOP Framework class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FrameworkTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function resultOfSayHelloMethodIsModifiedByWorldAdvice() {
		$targetClass = $this->objectManager->get('F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01');
		$this->assertSame('Hello World', $targetClass->sayHello());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function adviceRecoversFromException() {
		$targetClass = $this->objectManager->get('F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01');
		try {
			$targetClass->sayHelloAndThrow(TRUE);
		} catch(\Exception $e) {}
		$this->assertSame('Hello World', $targetClass->sayHelloAndThrow(FALSE));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function resultOfGreetMethodIsModifiedBySpecialNameAdvice() {
		$targetClass = $this->objectManager->get('F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01');
		$this->assertSame('Hello, me', $targetClass->greet('FLOW3'));
		$this->assertSame('Hello, Christopher', $targetClass->greet('Christopher'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function containWithSplObjectStorageInRuntimeEvaluation() {
		$targetClass = $this->objectManager->get('F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01');
		$name = new \F3\FLOW3\Tests\Functional\AOP\Fixtures\Name('FLOW3');
		$otherName = new \F3\FLOW3\Tests\Functional\AOP\Fixtures\Name('TYPO3');
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach($name);
		$targetClass->setCurrentName($name);
		$this->assertEquals('Hello, special guest', $targetClass->greetMany($splObjectStorage));
		$targetClass->setCurrentName(NULL);
		$this->assertEquals('Hello, FLOW3', $targetClass->greetMany($splObjectStorage));
		$targetClass->setCurrentName($otherName);
		$this->assertEquals('Hello, FLOW3', $targetClass->greetMany($splObjectStorage));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorAdvicesAreInvoked() {
		$targetClass = $this->objectManager->get('F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01');
		$this->assertSame('AVRO RJ100 is lousier than A-380', $targetClass->constructorResult);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function adviceInformationIsAlsoWhenTheTargetClassIsUnserialized() {
		$className = 'F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01';
		$targetClass = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		$this->assertSame('Hello, me', $targetClass->greet('FLOW3'));
	}

}
?>
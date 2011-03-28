<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\Object;

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

use \F3\FLOW3\Reflection\ClassReflection;

/**
 * Functional tests for the Proxy Compiler and related features
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProxyCompilerTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function proxyClassesStillContainAnnotationsFromItsOriginalClass() {
		$class = new ClassReflection('F3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassA');
		$method = $class->getMethod('setSomeProperty');

		$this->assertTrue($class->implementsInterface('F3\FLOW3\Object\Proxy\ProxyInterface'));
		$this->assertTrue($class->isTaggedWith('foo'));
		$this->assertTrue($class->isTaggedWith('bar'));
		$this->assertTrue($method->isTaggedWith('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classesAnnotatedWithProxyDisableAreNotProxied() {
		$singletonB = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB');
		$this->assertNotInstanceOf('F3\FLOW3\Object\Proxy\ProxyInterface', $singletonB);
	}
}
?>
<?php
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

/**
 * Functional tests for the Dependency Injection features
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DependencyInjectionTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function singletonObjectsCanBeInjectedIntoConstructorsOfSingletonObjects() {
		$objectA = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA');
		$objectB = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB');

		$this->assertSame($objectB, $objectA->getObjectB());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorInjectionCanHandleCombinationsOfRequiredAutowiredAndOptionalArguments() {
		$objectC = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassC');

			// Note: The "requiredArgument" is defined in the Objects.yaml of the FLOW3 package (testing context)
		$this->assertSame('this is required', $objectC->requiredArgument);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertiesAreReinjectedIfTheObjectIsUnserialized() {
		$className = 'F3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassA';

		$singletonA = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA');

		$prototypeA = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		$this->assertSame($singletonA, $prototypeA->getSingletonA());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function virtualObjectsDefinedInObjectsYamlCanUseAFactoryForTheirActualImplementation() {
		$prototypeA = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassAishInterface');

		$this->assertInstanceOf('F3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassA', $prototypeA);
		$this->assertSame('value defined in Objects.yaml', $prototypeA->getSomeProperty());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function constructorInjectionInSingletonCanHandleArgumentDefinedInSettings() {
		$objectC = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassC');

			// Note: The "settingsArgument" is defined in the Settings.yaml of the FLOW3 package (testing context)
		$this->assertSame('setting injected singleton value', $objectC->settingsArgument);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function singletonCanHandleInjectedPrototypeWithSettingArgument() {
		$objectD = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassD');

			// Note: The "settingsArgument" is defined in the Settings.yaml of the FLOW3 package (testing context)
		$this->assertSame('setting injected property value', $objectD->prototypeClassC->settingsArgument);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function singletonCanHandleInjectedPrototypeWithCustomFactory() {
		$objectD = $this->objectManager->get('F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassD');

			// Note: The "prototypeClassA" is defined with a custom factory in the Objects.yaml of the FLOW3 package (testing context)
		$this->assertNotNull($objectD->prototypeClassA);
		$this->assertSame('value defined in Objects.yaml', $objectD->prototypeClassA->getSomeProperty());
	}

}
?>
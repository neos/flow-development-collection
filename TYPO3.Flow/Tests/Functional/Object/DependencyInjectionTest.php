<?php
namespace TYPO3\FLOW3\Tests\Functional\Object;

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
 * Functional tests for the Dependency Injection features
 *
 */
class DependencyInjectionTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function singletonObjectsCanBeInjectedIntoConstructorsOfSingletonObjects() {
		$objectA = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA');
		$objectB = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB');

		$this->assertSame($objectB, $objectA->getObjectB());
	}

	/**
	 * @test
	 */
	public function constructorInjectionCanHandleCombinationsOfRequiredAutowiredAndOptionalArguments() {
		$objectC = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassC');

			// Note: The "requiredArgument" is defined in the Objects.yaml of the FLOW3 package (testing context)
		$this->assertSame('this is required', $objectC->requiredArgument);
	}

	/**
	 * @test
	 */
	public function propertiesAreReinjectedIfTheObjectIsUnserialized() {
		$className = 'TYPO3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassA';

		$singletonA = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA');

		$prototypeA = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		$this->assertSame($singletonA, $prototypeA->getSingletonA());
	}

	/**
	 * @test
	 */
	public function virtualObjectsDefinedInObjectsYamlCanUseAFactoryForTheirActualImplementation() {
		$prototypeA = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassAishInterface');

		$this->assertInstanceOf('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassA', $prototypeA);
		$this->assertSame('value defined in Objects.yaml', $prototypeA->getSomeProperty());
	}

	/**
	 * @test
	 */
	public function constructorInjectionInSingletonCanHandleArgumentDefinedInSettings() {
		$objectC = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassC');

			// Note: The "settingsArgument" is defined in the Settings.yaml of the FLOW3 package (testing context)
		$this->assertSame('setting injected singleton value', $objectC->settingsArgument);
	}

	/**
	 * @test
	 */
	public function singletonCanHandleInjectedPrototypeWithSettingArgument() {
		$objectD = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassD');

			// Note: The "settingsArgument" is defined in the Settings.yaml of the FLOW3 package (testing context)
		$this->assertSame('setting injected property value', $objectD->prototypeClassC->settingsArgument);
	}

	/**
	 * @test
	 */
	public function singletonCanHandleInjectedPrototypeWithCustomFactory() {
		$objectD = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassD');

			// Note: The "prototypeClassA" is defined with a custom factory in the Objects.yaml of the FLOW3 package (testing context)
		$this->assertNotNull($objectD->prototypeClassA);
		$this->assertSame('value defined in Objects.yaml', $objectD->prototypeClassA->getSomeProperty());
	}

	/**
	 * @test
	 */
	public function onCreationOfObjectInjectionInParentClassIsDoneOnlyOnce() {
		$prototypeDsub = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Object\Fixtures\PrototypeClassDsub');
		$this->assertSame(1, $prototypeDsub->injectionRuns);
	}
}
?>
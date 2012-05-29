<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Controller;

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
 * Testcase for the MVC Controller Argument
 *
 * @covers \TYPO3\FLOW3\Mvc\Controller\MvcPropertyMappingConfiguration
 */
class MvcPropertyMappingConfigurationTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\MvcPropertyMappingConfiguration
	 */
	protected $mvcPropertyMappingConfiguration;

	/**
	 *
	 */
	public function setUp() {
		$this->mvcPropertyMappingConfiguration = new \TYPO3\FLOW3\Mvc\Controller\MvcPropertyMappingConfiguration();
	}

	/**
	 * @return array Signature: $methodToTestForFluentInterface [, $argumentsForMethod = array() ]
	 */
	public function fluentInterfaceMethodsDataProvider() {
		return array(
			array('allowCreationForSubProperty', array('some.property.path')),
			array('allowModificationForSubProperty', array('some.property.path')),
			array('setTargetTypeForSubProperty', array('some.property.path', 'dummy\Target\Type')),
			array('allowOverrideTargetType'),
		);
	}

	/**
	 * @test
	 * @dataProvider fluentInterfaceMethodsDataProvider
	 */
	public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = array()) {
		$actualResult = call_user_func_array(array($this->mvcPropertyMappingConfiguration, $methodToTestForFluentInterface), $argumentsForMethod);
		$this->assertSame($this->mvcPropertyMappingConfiguration, $actualResult);
	}
}
?>
<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Property Mapping Configuration Service
 */
class MvcPropertyMappingConfigurationServiceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data provider for generating the list of trusted properties
	 *
	 * @return array
	 */
	public function dataProviderForgenerateTrustedPropertiesToken() {
		return array(
			'Simple Case - Empty' => array(
				array(),
				array(),
			),
			'Simple Case - Single Value' => array(
				array('field1'),
				array('field1' => 1),
			),
			'Simple Case - Two Values' => array(
				array('field1', 'field2'),
				array(
					'field1' => 1,
					'field2' => 1
				),
			),
			'Recursion' => array(
				array('field1', 'field[subfield1]', 'field[subfield2]'),
				array(
					'field1' => 1,
					'field' => array(
						'subfield1' => 1,
						'subfield2' => 1
					)
				),
			),
			'recursion with duplicated field name' => array(
				array('field1', 'field[subfield1]', 'field[subfield2]', 'field1'),
				array(
					'field1' => 1,
					'field' => array(
						'subfield1' => 1,
						'subfield2' => 1
					)
				),
			),
			'Recursion with un-named fields at the end (...[]). There, they should be made explicit by increasing the counter' => array(
				array('field1', 'field[subfield1][]', 'field[subfield1][]', 'field[subfield2]'),
				array(
					'field1' => 1,
					'field' => array(
						'subfield1' => array(
							0 => 1,
							1 => 1
						),
						'subfield2' => 1
					)
				),
			),
		);
	}

	/**
	 * Data Provider for invalid values in generating the list of trusted properties,
	 * which should result in an exception
	 *
	 * @return array
	 */
	public function dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues() {
		return array(
			'Overriding form fields (string overridden by array) - 1' => array(
				array('field1', 'field2', 'field2[bla]', 'field2[blubb]'),
			),
			'Overriding form fields (string overridden by array) - 2' => array(
				array('field1', 'field2[bla]', 'field2[bla][blubb][blubb]'),
			),
			'Overriding form fields (array overridden by string) - 1' => array(
				array('field1', 'field2[bla]', 'field2[blubb]', 'field2'),
			),
			'Overriding form fields (array overridden by string) - 2' => array(
				array('field1', 'field2[bla][blubb][blubb]', 'field2[bla]'),
			),
			'Empty [] not as last argument' => array(
				array('field1', 'field2[][bla]'),
			)

		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForgenerateTrustedPropertiesToken
	 */
	public function generateTrustedPropertiesTokenGeneratesTheCorrectHashesInNormalOperation($input, $expected) {
		$requestHashService = $this->getMock('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService', array('serializeAndHashFormFieldArray'));
		$requestHashService->expects($this->once())->method('serializeAndHashFormFieldArray')->with($expected);
		$requestHashService->generateTrustedPropertiesToken($input);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues
	 * @expectedException \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function generateTrustedPropertiesTokenThrowsExceptionInWrongCases($input) {
		$requestHashService = $this->getMock('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService', array('serializeAndHashFormFieldArray'));
		$requestHashService->generateTrustedPropertiesToken($input);
	}

	/**
	 * @test
	 */
	public function serializeAndHashFormFieldArrayWorks() {
		$formFieldArray = array(
			'bla' => array(
				'blubb' => 1,
				'hu' => 1
			)
		);
		$mockHash = '12345';

		$hashService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService'), array('appendHmac'));
		$hashService->expects($this->once())->method('appendHmac')->with(serialize($formFieldArray))->will($this->returnValue(serialize($formFieldArray) . $mockHash));

		$requestHashService = $this->getMock($this->buildAccessibleProxy('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService'), array('dummy'));
		$requestHashService->_set('hashService', $hashService);

		$expected = serialize($formFieldArray) . $mockHash;
		$actual = $requestHashService->_call('serializeAndHashFormFieldArray', $formFieldArray);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationDoesNothingIfTrustedPropertiesAreNotSet() {
		$request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->setMethods(array('getInternalArgument'))->disableOriginalConstructor()->getMock();
		$request->expects($this->any())->method('getInternalArgument')->with('__trustedProperties')->will($this->returnValue(NULL));
		$arguments = new \TYPO3\Flow\Mvc\Controller\Arguments();

		$requestHashService = new \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
		$requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

		// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationReturnsEarlyIfNoTrustedPropertiesAreSet() {
		$trustedProperties = array(
			'foo' => 1
		);
		$this->initializePropertyMappingConfiguration($trustedProperties);
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationReturnsEarlyIfArgumentIsUnknown() {
		$trustedProperties = array(
			'nonExistingArgument' => 1
		);
		$arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
		$this->assertFalse($arguments->hasArgument('nonExistingArgument'));
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationSetsModificationAllowedIfIdentityPropertyIsSet() {
		$trustedProperties = array(
			'foo' => array(
				'__identity' => 1,
				'nested' => array(
					'__identity' => 1,
				)
			)
		);
		$arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
		$propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
		$this->assertTrue($propertyMappingConfiguration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
		$this->assertNull($propertyMappingConfiguration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

		$this->assertTrue($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
		$this->assertNull($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertFalse($propertyMappingConfiguration->forProperty('nested')->shouldMap('someProperty'));
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationSetsCreationAllowedIfIdentityPropertyIsNotSet() {
		$trustedProperties = array(
			'foo' => array(
				'bar' => array()
			)
		);
		$arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
		$propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
		$this->assertNull($propertyMappingConfiguration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
		$this->assertTrue($propertyMappingConfiguration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

		$this->assertNull($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
		$this->assertTrue($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertFalse($propertyMappingConfiguration->forProperty('bar')->shouldMap('someProperty'));
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationSetsAllowedFields() {
		$trustedProperties = array(
			'foo' => array(
				'bar' => 1
			)
		);
		$arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
		$propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
		$this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
		$this->assertTrue($propertyMappingConfiguration->shouldMap('bar'));
	}

	/**
	 * @test
	 */
	public function initializePropertyMappingConfigurationSetsAllowedFieldsRecursively() {
		$trustedProperties = array(
			'foo' => array(
				'bar' => array(
					'foo' => 1
				)
			)
		);
		$arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
		$propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
		$this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
		$this->assertTrue($propertyMappingConfiguration->shouldMap('bar'));
		$this->assertTrue($propertyMappingConfiguration->forProperty('bar')->shouldMap('foo'));
	}


	/**
	 * Helper which initializes the property mapping configuration and returns arguments
	 *
	 * @param array $trustedProperties
	 * @return \TYPO3\Flow\Mvc\Controller\Arguments
	 */
	protected function initializePropertyMappingConfiguration(array $trustedProperties) {
		$request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->setMethods(array('getInternalArgument'))->disableOriginalConstructor()->getMock();
		$request->expects($this->any())->method('getInternalArgument')->with('__trustedProperties')->will($this->returnValue('fooTrustedProperties'));
		$arguments = new \TYPO3\Flow\Mvc\Controller\Arguments();
		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService', array('validateAndStripHmac'));
		$mockHashService->expects($this->once())->method('validateAndStripHmac')->with('fooTrustedProperties')->will($this->returnValue(serialize($trustedProperties)));

		$arguments->addNewArgument('foo', 'something');
		$this->inject($arguments->getArgument('foo'), 'propertyMappingConfiguration', new \TYPO3\Flow\Property\PropertyMappingConfiguration());

		$requestHashService = new \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService();
		$this->inject($requestHashService, 'hashService', $mockHashService);

		$requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

		return $arguments;
	}
}

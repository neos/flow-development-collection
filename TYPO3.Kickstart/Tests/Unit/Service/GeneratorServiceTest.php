<?php
namespace TYPO3\Kickstart\Tests\Unit\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Kickstart".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the generator service
 *
 */
class GeneratorServiceTest extends \TYPO3\Flow\Tests\UnitTestCase {
	/**
	 * @test
	 */
	public function normalizeFieldDefinitionsConvertsBoolTypeToBoolean() {
		$service = $this->getMock($this->buildAccessibleProxy('TYPO3\Kickstart\Service\GeneratorService'), array('dummy'));
		$fieldDefinitions = array(
			'field' => array(
				'type' => 'bool'
			)
		);
		$normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions);
		$this->assertEquals('boolean', $normalizedFieldDefinitions['field']['type']);
	}

	/**
	 * @test
	 */
	public function normalizeFieldDefinitionsPrefixesGlobalClassesWithBackslash() {
		$service = $this->getMock($this->buildAccessibleProxy('TYPO3\Kickstart\Service\GeneratorService'), array('dummy'));
		$fieldDefinitions = array(
			'field' => array(
				'type' => 'DateTime'
			)
		);
		$normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions);
		$this->assertEquals('\DateTime', $normalizedFieldDefinitions['field']['type']);
	}

	/**
	 * @test
	 */
	public function normalizeFieldDefinitionsPrefixesLocalTypesWithNamespace() {
		$service = $this->getMock($this->buildAccessibleProxy('TYPO3\Kickstart\Service\GeneratorService'), array('dummy'));
		$fieldDefinitions = array(
			'field' => array(
				'type' => 'Foo'
			)
		);
		$normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions, 'TYPO3\Testing\Domain\Model');
		$this->assertEquals('\TYPO3\Testing\Domain\Model\Foo', $normalizedFieldDefinitions['field']['type']);
	}
}
?>
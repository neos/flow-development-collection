<?php
namespace F3\Kickstart\Tests\Unit\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "Kickstart".                  *
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
 * Testcase for the generator service
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class GeneratorServiceTest extends \F3\FLOW3\Tests\UnitTestCase {
	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function normalizeFieldDefinitionsConvertsBoolTypeToBoolean() {
		$service = $this->getMock($this->buildAccessibleProxy('F3\Kickstart\Service\GeneratorService'), array('dummy'));
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
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function normalizeFieldDefinitionsPrefixesGlobalClassesWithBackslash() {
		$service = $this->getMock($this->buildAccessibleProxy('F3\Kickstart\Service\GeneratorService'), array('dummy'));
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
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function normalizeFieldDefinitionsPrefixesLocalTypesWithNamespace() {
		$service = $this->getMock($this->buildAccessibleProxy('F3\Kickstart\Service\GeneratorService'), array('dummy'));
		$fieldDefinitions = array(
			'field' => array(
				'type' => 'Foo'
			)
		);		
		$normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions, 'F3\Testing\Domain\Model');
		$this->assertEquals('\F3\Testing\Domain\Model\Foo', $normalizedFieldDefinitions['field']['type']);
	}
}
?>
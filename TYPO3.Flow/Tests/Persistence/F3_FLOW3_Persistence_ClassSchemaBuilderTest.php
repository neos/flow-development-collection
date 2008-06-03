<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

require_once('Fixture/F3_FLOW3_Tests_Persistence_Fixture_Entity1.php');
require_once('Fixture/F3_FLOW3_Tests_Persistence_Fixture_ValueObject1.php');

/**
 * Testcase for the Class Schema Builder
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Persistence_ClassSchemaBuilderTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildThrowsExceptionOnSpecifyingANonExistingClass() {
		try {
			$builtClassSchema = F3_FLOW3_Persistence_ClassSchemaBuilder::build('NonExistingClass');
			$this->fail('No exception thrown.');
		} catch (F3_FLOW3_Persistence_Exception_InvalidClass $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classSchemaOnlyContainsNonTransientProperties() {
		$expectedProperties = array('someString', 'someInteger', 'someFloat', 'someDate', 'someBoolean');

		$builtClassSchema = F3_FLOW3_Persistence_ClassSchemaBuilder::build('F3_FLOW3_Tests_Persistence_Fixture_Entity1');
		$actualProperties = array_keys($builtClassSchema->getProperties());
		sort($expectedProperties);
		sort($actualProperties);
		$this->assertEquals($expectedProperties, $actualProperties);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertyTypesAreDetectedFromVarAnnotations() {
		$expectedProperties = array(
			'someBoolean' => 'boolean',
			'someString' => 'string',
			'someInteger' => 'integer',
			'someFloat' => 'float',
			'someDate' => 'DateTime'
		);

		$builtClassSchema = F3_FLOW3_Persistence_ClassSchemaBuilder::build('F3_FLOW3_Tests_Persistence_Fixture_Entity1');
		$actualProperties = $builtClassSchema->getProperties();
		asort($expectedProperties);
		asort($actualProperties);
		$this->assertEquals($expectedProperties, $actualProperties);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultModelTypeIsEntity() {
		$builtClassSchema = F3_FLOW3_Persistence_ClassSchemaBuilder::build('F3_FLOW3_Tests_Persistence_Fixture_Entity1');
		$this->assertEquals($builtClassSchema->getModelType(), F3_FLOW3_Persistence_ClassSchema::MODELTYPE_ENTITY);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modelTypeValueObjectIsDefinedByValueObjectAnnotation() {
		$builtClassSchema = F3_FLOW3_Persistence_ClassSchemaBuilder::build('F3_FLOW3_Tests_Persistence_Fixture_ValueObject1');
		$this->assertEquals($builtClassSchema->getModelType(), F3_FLOW3_Persistence_ClassSchema::MODELTYPE_VALUEOBJECT);
	}
}

?>
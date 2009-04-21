<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the validator resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ValidatorResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorClassNameReturnsNullIfNoValidatorIsAvailable() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$validatorResolver = new \F3\FLOW3\Validation\ValidatorResolver($mockObjectManager);
		$this->assertEquals(NULL, $validatorResolver->resolveValidatorClassName('NotExistantClass'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorReturnsTheCorrectValidator() {
		$className = uniqid('Test');
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ObjectValidatorInterface', array(), array(), $className . 'Validator');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->any())->method('getObject')->will($this->returnValue($mockValidator));

		$validatorResolver = new \F3\FLOW3\Validation\ValidatorResolver($mockObjectManager);
		$validator = $validatorResolver->createValidator($className);
		$this->assertSame($mockValidator, $validator);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValidatorClassNameCallsUnifyDataType() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('unifyDataType'), array($mockObjectManager));
		$mockValidator->expects($this->once())->method('unifyDataType')->with('someDataType');
		$mockValidator->resolveValidatorClassName('someDataType');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeCorrectlyRenamesPHPDataTypes() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'integer'));
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'int'));
		$this->assertEquals('Text', $mockValidator->_call('unifyDataType', 'string'));
		$this->assertEquals('Array', $mockValidator->_call('unifyDataType', 'array'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'float'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'double'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'boolean'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'number'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'numeric'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeRenamesMixedToRaw() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('unifyDataType', 'mixed'));
	}
}

?>
<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the validator resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ValidatorResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException F3\FLOW3\Validation\Exception\NoValidatorFound
	 */
	public function resolveValidatorThrowsExceptionIfNoValidatorIsAvailable() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$validatorResolver = new \F3\FLOW3\Validation\ValidatorResolver($mockObjectManager);
		$validatorResolver->resolveValidator('NotExistantClass');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function resolveValidatorReturnsTheCorrectValidator() {
		$mockValidator = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface', array(), array(), 'F3\Virtual\BasicClassValidator');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->any())->method('getObject')->will($this->returnValue($mockValidator));

		$validatorResolver = new \F3\FLOW3\Validation\ValidatorResolver($mockObjectManager);
		$validator = $validatorResolver->resolveValidator('F3\Virtual\BasicClass');

		if (!($validator instanceof \F3\Virtual\BasicClassValidator)) {
			$this->fail('The validator resolver did not return the correct validator object.');
		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException F3\FLOW3\Validation\Exception\NoValidatorFound
	 */
	public function resolveValidatorThrowsExceptionIfAvailableValidatorDoesNotImplementTheValidatorInterface() {
		$className = uniqid('Test');
		$validatorName = $className . 'Validator';
		eval('class ' . $className . 'Validator {}');
		$mockValidator = new $validatorName;
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->any())->method('getObject')->will($this->returnValue($mockValidator));

		$validatorResolver = new \F3\FLOW3\Validation\ValidatorResolver($mockObjectManager);
		$validatorResolver->resolveValidator($className);
	}
}

?>
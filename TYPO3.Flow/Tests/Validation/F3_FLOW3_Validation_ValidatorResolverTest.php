<?php
declare(encoding = 'utf-8');

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
 * @version $Id: F3_FLOW3_Validation_ValidatorResolverTest.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Testcase for the validator resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_ValidatorResolverTest.php 688 2008-04-03 09:35:36Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_ValidatorResolverTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorThrowsExceptionIfNoValidatorIsAvailable() {
		$validatorResolver = $this->componentManager->getComponent('F3_FLOW3_Validation_ValidatorResolver');

		try {
			$validatorResolver->resolveValidator('NotExistantClass');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Validation_Exception_NoValidatorFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorReturnsTheCorrectValidator() {
		$validatorResolver = $this->componentManager->getComponent('F3_FLOW3_Validation_ValidatorResolver');
		$validator = $validatorResolver->resolveValidator('F3_TestPackage_BasicClass');

		if(!($validator instanceof F3_TestPackage_BasicClassValidator)) $this->fail('The validator resolver did not return the correct validator object.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorThrowsExceptionIfAvailableValidatorDoesNotImplementTheValidatorInterface() {
	$validatorResolver = $this->componentManager->getComponent('F3_FLOW3_Validation_ValidatorResolver');

		try {
			$validatorResolver->resolveValidator('F3_TestPackage_SomeTest');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Validation_Exception_NoValidatorFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorNameThrowsExceptionIfNoValidatorIsAvailable() {
		$validatorResolver = $this->componentManager->getComponent('F3_FLOW3_Validation_ValidatorResolver');

		try {
			$validatorResolver->resolveValidatorName('NotExistantClass');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Validation_Exception_NoValidatorFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorNameReturnsTheCorrectValidator() {
		$validatorResolver = $this->componentManager->getComponent('F3_FLOW3_Validation_ValidatorResolver');
		$validatorName = $validatorResolver->resolveValidatorName('F3_TestPackage_BasicClass');

		$this->assertEquals($validatorName, 'F3_TestPackage_BasicClassValidator', 'The validator resolver did not return the correct validator name.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorNameThrowsExceptionIfAvailableValidatorDoesNotImplementTheValidatorInterface() {
	$validatorResolver = $this->componentManager->getComponent('F3_FLOW3_Validation_ValidatorResolver');

		try {
			$validatorResolver->resolveValidatorName('F3_TestPackage_SomeTest');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Validation_Exception_NoValidatorFound $exception) {

		}
	}
}

?>

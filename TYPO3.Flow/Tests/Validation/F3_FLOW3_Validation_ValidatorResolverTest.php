<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Validation;

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
class ValidatorResolverTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorThrowsExceptionIfNoValidatorIsAvailable() {
		$validatorResolver = $this->objectManager->getObject('F3::FLOW3::Validation::ValidatorResolver');

		try {
			$validatorResolver->resolveValidator('NotExistantClass');
			$this->fail('No exception was thrown.');
		} catch (F3::FLOW3::Validation::Exception::NoValidatorFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorReturnsTheCorrectValidator() {
		$validatorResolver = $this->objectManager->getObject('F3::FLOW3::Validation::ValidatorResolver');
		$validator = $validatorResolver->resolveValidator('F3::TestPackage::BasicClass');

		if (!($validator instanceof F3::TestPackage::BasicClassValidator)) $this->fail('The validator resolver did not return the correct validator object.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorThrowsExceptionIfAvailableValidatorDoesNotImplementTheValidatorInterface() {
		$validatorResolver = $this->objectManager->getObject('F3::FLOW3::Validation::ValidatorResolver');

		try {
			$validatorResolver->resolveValidator('F3::TestPackage::SomeTest');
			$this->fail('No exception was thrown.');
		} catch (F3::FLOW3::Validation::Exception::NoValidatorFound $exception) {

		}
	}
}

?>
<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Validation::Validator;

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
 * @version $Id:F3::FLOW3::Validation::Validator::ChainTest.php 845 2008-05-17 16:04:59Z k-fish $
 */

/**
 * Testcase for ValidatorChains
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Validation::Validator::ChainTest.php 845 2008-05-17 16:04:59Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ChainTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingValidatorsToAValidatorChainWorks() {
		$validatorChain = new F3::FLOW3::Validation::Validator::Chain();
		$validatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');

		$index = $validatorChain->addValidator($validatorObject);

		$this->assertEquals($validatorObject, $validatorChain->getValidator($index));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function allValidatorsInTheChainAreInvocedCorrectly() {
		$validatorChain = new F3::FLOW3::Validation::Validator::Chain();
		$validatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValidProperty');
		$secondValidatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValidProperty');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->isValidProperty('some subject', new F3::FLOW3::Validation::Errors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorChainReturnsTrueIfAllChainedValidatorsReturnTrue() {
		$validatorChain = new F3::FLOW3::Validation::Validator::Chain();
		$validatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$secondValidatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertTrue($validatorChain->isValidProperty('some subject', new F3::FLOW3::Validation::Errors()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingAValidatorOfTheValidatorChainWorks() {
		$validatorChain = new F3::FLOW3::Validation::Validator::Chain();
		$validatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');
		$secondValidatorObject = $this->getMock('F3::FLOW3::Validation::ValidatorInterface');
		$validatorChain->addValidator($validatorObject);
		$index = $validatorChain->addValidator($secondValidatorObject);

		$validatorChain->removeValidator($index);

		try {
			$validatorChain->getValidator($index);
			$this->fail('The validator chain did not remove the validator with the given index.');
		} catch(F3::FLOW3::Validation::Exception::InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function accessingANotExistingValidatorIndexThrowsException() {
		$validatorChain = new F3::FLOW3::Validation::Validator::Chain();

		try {
			$validatorChain->getValidator(100);
			$this->fail('The validator chain did throw an error on accessing an invalid validator index.');
		} catch(F3::FLOW3::Validation::Exception::InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorChain = new F3::FLOW3::Validation::Validator::Chain();

		try {
			$validatorChain->removeValidator(100);
			$this->fail('The validator chain did throw an error on removing an invalid validator index.');
		} catch(F3::FLOW3::Validation::Exception::InvalidChainIndex $exception) {

		}
	}
}

?>
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
 * @version $Id: F3_FLOW3_Validation_Validator_ChainTest.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Testcase for OjbectValidatorChains
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_Validator_ChainTest.php 688 2008-04-03 09:35:36Z andi $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_ObjectChainTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingValidatorsToAnObjectValidatorChainWorks() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');

		$index = $validatorChain->addValidator($validatorObject);

		$this->assertEquals($validatorObject, $validatorChain->getValidator($index));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canValidateAsksAllValidatorsInTheChainCorrectly() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('canValidate');
		$secondValidatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('canValidate');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->canValidate('some class');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidPropertyInvocesAllValidatorsInTheChainCorrectly() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('isValidProperty');
		$secondValidatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValidProperty');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->isValidProperty('some class', 'some property', 'some value', new F3_FLOW3_Validation_Errors);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyInvocesAllValidatorsInTheChainCorrectly() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('validateProperty');
		$secondValidatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('validateProperty');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->validateProperty('some object', 'some property', new F3_FLOW3_Validation_Errors);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validateInvocesAllValidatorsInTheChainCorrectly() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('validate');
		$secondValidatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('validate');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->validate('some object', new F3_FLOW3_Validation_Errors);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidPropertyReturnsTrueIfAllChainedValidatorsReturnTrue() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$validatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$secondValidatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertTrue($validatorChain->isValidProperty('some class', 'some property', 'some value', new F3_FLOW3_Validation_Errors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingAValidatorOfTheObjectValidatorChainWorks() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$secondValidatorObject = $this->getMock('F3_FLOW3_Validation_ObjectValidatorInterface');
		$validatorChain->addValidator($validatorObject);
		$index = $validatorChain->addValidator($secondValidatorObject);

		$validatorChain->removeValidator($index);

		try {
			$validatorChain->getValidator($index);
			$this->fail('The validator chain did not remove the validator with the given index.');
		} catch(F3_FLOW3_Validation_Exception_InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function accessingANotExistingObjectValidatorIndexThrowsException() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();

		try {
			$validatorChain->getValidator(100);
			$this->fail('The validator chain did throw an error on accessing an invalid validator index.');
		} catch(F3_FLOW3_Validation_Exception_InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingANotExistingObjectValidatorIndexThrowsException() {
		$validatorChain = new F3_FLOW3_Validation_Validator_ObjectValidatorChain();

		try {
			$validatorChain->removeValidator(100);
			$this->fail('The validator chain did throw an error on removing an invalid validator index.');
		} catch(F3_FLOW3_Validation_Exception_InvalidChainIndex $exception) {

		}
	}
}

?>

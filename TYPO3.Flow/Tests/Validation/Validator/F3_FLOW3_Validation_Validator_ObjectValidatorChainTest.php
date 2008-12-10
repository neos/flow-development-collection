<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * Testcase for OjbectValidatorChains
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ObjectValidatorChainTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingValidatorsToAnObjectValidatorChainWorks() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');

		$index = $validatorChain->addValidator($validatorObject);

		$this->assertEquals($validatorObject, $validatorChain->getValidator($index));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canValidateAsksAllValidatorsInTheChainCorrectly() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('canValidate');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
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
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('isValidProperty');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValidProperty');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->isValidProperty('some class', 'some property', 'some value', new \F3\FLOW3\Validation\Errors);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatePropertyInvocesAllValidatorsInTheChainCorrectly() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('validateProperty');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('validateProperty');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->validateProperty('some object', 'some property', new \F3\FLOW3\Validation\Errors);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validateInvocesAllValidatorsInTheChainCorrectly() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$validatorObject->expects($this->once())->method('validate');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('validate');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->validate('some object', new \F3\FLOW3\Validation\Errors);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValidPropertyReturnsTrueIfAllChainedValidatorsReturnTrue() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$validatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertTrue($validatorChain->isValidProperty('some class', 'some property', 'some value', new \F3\FLOW3\Validation\Errors));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingAValidatorOfTheObjectValidatorChainWorks() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');
		$validatorChain->addValidator($validatorObject);
		$index = $validatorChain->addValidator($secondValidatorObject);

		$validatorChain->removeValidator($index);

		try {
			$validatorChain->getValidator($index);
			$this->fail('The validator chain did not remove the validator with the given index.');
		} catch(\F3\FLOW3\Validation\Exception\InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function accessingANotExistingObjectValidatorIndexThrowsException() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();

		try {
			$validatorChain->getValidator(100);
			$this->fail('The validator chain did throw an error on accessing an invalid validator index.');
		} catch(\F3\FLOW3\Validation\Exception\InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingANotExistingObjectValidatorIndexThrowsException() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ObjectValidatorChain();

		try {
			$validatorChain->removeValidator(100);
			$this->fail('The validator chain did throw an error on removing an invalid validator index.');
		} catch(\F3\FLOW3\Validation\Exception\InvalidChainIndex $exception) {

		}
	}
}

?>
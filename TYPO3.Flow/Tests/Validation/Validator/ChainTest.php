<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * Testcase for ValidatorChains
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ChainTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingValidatorsToAValidatorChainWorks() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\Chain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');

		$index = $validatorChain->addValidator($validatorObject);

		$this->assertEquals($validatorObject, $validatorChain->getValidator($index));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function allValidatorsInTheChainAreInvocedCorrectly() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\Chain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValidProperty');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValidProperty');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->isValidProperty('some subject', new \F3\FLOW3\Validation\Errors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorChainReturnsTrueIfAllChainedValidatorsReturnTrue() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\Chain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValidProperty')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertTrue($validatorChain->isValidProperty('some subject', new \F3\FLOW3\Validation\Errors()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidChainIndex
	 */
	public function removingAValidatorOfTheValidatorChainWorks() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\Chain();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\ValidatorInterface');
		$validatorChain->addValidator($validatorObject);
		$index = $validatorChain->addValidator($secondValidatorObject);

		$validatorChain->removeValidator($index);

		$validatorChain->getValidator($index);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidChainIndex
	 */
	public function accessingANotExistingValidatorIndexThrowsException() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\Chain();

		$validatorChain->getValidator(100);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Validation\Exception\InvalidChainIndex
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\Chain();

		$validatorChain->removeValidator(100);
	}
}

?>
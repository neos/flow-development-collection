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
class ChainValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingValidatorsToAValidatorChainWorks() {
		$proxyClassName = $this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\ChainValidator');
		$validatorChain = new $proxyClassName;

		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorChain->addValidator($mockValidator);
		$this->assertTrue($validatorChain->_get('validators')->contains($mockValidator));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allValidatorsInTheChainAreCalledIfEachOfThemReturnsTrue() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ChainValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$validatorChain->isValid('some subject');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorChainReturnsTrueIfAllChainedValidatorsReturnTrue() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ChainValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertTrue($validatorChain->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validatorChainImmediatelyReturnsFalseIfOneValidatorsReturnFalse() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ChainValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->never())->method('isValid');

		$validatorChain->addValidator($validatorObject);
		$validatorChain->addValidator($secondValidatorObject);

		$this->assertFalse($validatorChain->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removingAValidatorOfTheValidatorChainWorks() {
		$validatorChain = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\ChainValidator'), array('dummy'), array(), '', TRUE);

		$validator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$validatorChain->addValidator($validator1);
		$validatorChain->addValidator($validator2);

		$validatorChain->removeValidator($validator1);

		$this->assertFalse($validatorChain->_get('validators')->contains($validator1));
		$this->assertTrue($validatorChain->_get('validators')->contains($validator2));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException F3\FLOW3\Validation\Exception\NoSuchValidator
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ChainValidator;
		$validator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorChain->removeValidator($validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function countReturnesTheNumberOfValidatorsContainedInThechain() {
		$validatorChain = new \F3\FLOW3\Validation\Validator\ChainValidator;

		$validator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$this->assertSame(0, count($validatorChain));

		$validatorChain->addValidator($validator1);
		$validatorChain->addValidator($validator2);

		$this->assertSame(2, count($validatorChain));
	}
}

?>
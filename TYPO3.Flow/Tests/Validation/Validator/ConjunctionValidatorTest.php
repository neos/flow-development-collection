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
 * Testcase for the Conjunction Validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ConjunctionValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingValidatorsToAJunctionValidatorWorks() {
		$proxyClassName = $this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\ConjunctionValidator');
		$conjunctionValidator = new $proxyClassName;

		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$conjunctionValidator->addValidator($mockValidator);
		$this->assertTrue($conjunctionValidator->_get('validators')->contains($mockValidator));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsFalse() {
		$validatorConjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));
		$secondValidatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$thirdValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$thirdValidatorObject->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		$validatorConjunction->addValidator($validatorObject);
		$validatorConjunction->addValidator($secondValidatorObject);
		$validatorConjunction->addValidator($thirdValidatorObject);

		$validatorConjunction->isValid('some subject');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorConjunctionReturnsTrueIfAllJunctionedValidatorsReturnTrue() {
		$validatorConjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$secondValidatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$secondValidatorObject->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$validatorConjunction->addValidator($validatorObject);
		$validatorConjunction->addValidator($secondValidatorObject);

		$this->assertTrue($validatorConjunction->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function validatorConjunctionReturnsFalseIfOneValidatorReturnsFalse() {
		$validatorConjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();
		$validatorObject = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorObject->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$validatorObject->expects($this->any())->method('getErrors')->will($this->returnValue(array()));

		$validatorConjunction->addValidator($validatorObject);

		$this->assertFalse($validatorConjunction->isValid('some subject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removingAValidatorOfTheValidatorConjunctionWorks() {
		$validatorConjunction = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\Validator\ConjunctionValidator'), array('dummy'), array(), '', TRUE);

		$validator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$validatorConjunction->addValidator($validator1);
		$validatorConjunction->addValidator($validator2);

		$validatorConjunction->removeValidator($validator1);

		$this->assertFalse($validatorConjunction->_get('validators')->contains($validator1));
		$this->assertTrue($validatorConjunction->_get('validators')->contains($validator2));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException F3\FLOW3\Validation\Exception\NoSuchValidator
	 */
	public function removingANotExistingValidatorIndexThrowsException() {
		$validatorConjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();
		$validator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validatorConjunction->removeValidator($validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function countReturnesTheNumberOfValidatorsContainedInTheConjunction() {
		$validatorConjunction = new \F3\FLOW3\Validation\Validator\ConjunctionValidator();

		$validator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$validator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$this->assertSame(0, count($validatorConjunction));

		$validatorConjunction->addValidator($validator1);
		$validatorConjunction->addValidator($validator2);

		$this->assertSame(2, count($validatorConjunction));
	}
}

?>
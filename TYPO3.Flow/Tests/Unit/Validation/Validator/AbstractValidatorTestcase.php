<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Abstract Validator
 *
 */
abstract class AbstractValidatorTestcase extends \TYPO3\FLOW3\Tests\UnitTestCase {

	protected $validatorClassName;

	/**
	 *
	 * @var \TYPO3\FLOW3\Validation\Validator\ValidatorInterface
	 */
	protected $validator;

	public function setUp() {
		$this->validator = $this->getValidator();
	}

	protected function getValidator($options = array()) {
		return $this->getAccessibleMock($this->validatorClassName, array('dummy'), array($options), '', TRUE);
	}

	protected function validatorOptions($options) {
		$this->validator = $this->getValidator($options);
	}
}

?>
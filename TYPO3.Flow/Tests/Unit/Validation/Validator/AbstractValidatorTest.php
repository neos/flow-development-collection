<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
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
class AbstractValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	protected $validator;

	public function setUp() {
		$this->validator = $this->getAccessibleMockForAbstractClass('\TYPO3\Flow\Validation\Validator\AbstractValidator', array(), '', FALSE );
		$this->validator->_set( 'supportedOptions', array(
			'placeHolder'   	=> array('default', 'Desc', 'mixed', FALSE),
			'secondPlaceHolder' => array('default', 'Desc', 'mixed'),
			'thirdPlaceHolder'  => array('default', 'Desc', 'mixed', TRUE),
		) );
	}

	/**
	 * @test
	 */
	public function abstractValidatorConstructWithRequiredOptionShouldNotFail() {

		$this->validator->__construct( array( 'thirdPlaceHolder' => 'dummy' ) );

		$this->assertInstanceOf('\TYPO3\Flow\Validation\Validator\AbstractValidator', $this->validator);

	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 */
	public function abstractValidatorConstructWithoutRequiredOptionShouldFail() {

		$this->validator->__construct( array() );

	}

}

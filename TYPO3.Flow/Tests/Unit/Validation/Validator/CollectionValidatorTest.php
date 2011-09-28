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

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the collection validator
 *
 */
class CollectionValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\CollectionValidator';

	protected $mockValidatorResolver;

	public function setUp() {
		parent::setUp();
		$this->mockValidatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$this->validator->_set('validatorResolver', $this->mockValidatorResolver);
	}

	/**
	 * @test
	 */
	public function collectionValidatorReturnsNoErrorsForANullValue() {
		$this->assertFalse($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 */
	public function collectionValidatorFailsForAValueNotBeeingACollection() {
		$this->assertTrue($this->validator->validate(new \StdClass())->hasErrors());
	}

	/**
	 * @test
	 */
	public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator() {
		$this->validator->_set('options', array('elementValidator' => 'EmailAddress'));
		$this->mockValidatorResolver->expects($this->exactly(4))->method('createValidator')->with('EmailAddress')->will($this->returnValue(new \TYPO3\FLOW3\Validation\Validator\EmailAddressValidator()));

		$arrayOfEmailAddresses = array(
			'andreas.foerthner@netlogix.de',
			'not a valid address',
			'robert@typo3.org',
			'also not valid'
		);

		$result = $this->validator->validate($arrayOfEmailAddresses);

		$this->assertTrue($result->hasErrors());
		$this->assertEquals(2, count($result->getFlattenedErrors()));
	}
}
?>
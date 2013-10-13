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

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the collection validator
 *
 */
class CollectionValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\CollectionValidator';

	protected $mockValidatorResolver;

	public function setUp() {
		parent::setUp();
		$this->mockValidatorResolver = $this->getMock('TYPO3\Flow\Validation\ValidatorResolver', array(), array(), '', FALSE);
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
	public function collectionValidatorFailsForAValueNotBeingACollection() {
		$this->assertTrue($this->validator->validate(new \StdClass())->hasErrors());
	}

	/**
	 * @test
	 */
	public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator() {
		$this->validator->_set('options', array('elementValidator' => 'EmailAddress'));
		$this->mockValidatorResolver->expects($this->exactly(4))->method('createValidator')->with('EmailAddress')->will($this->returnValue(new \TYPO3\Flow\Validation\Validator\EmailAddressValidator()));

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

	/**
	 * @test
	 */
	public function collectionValidatorValidatesNestedObjectStructuresWithoutEndlessLooping() {
		$classNameA = 'A' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameA . '{ public $b = array(); public $integer = 5; }');
		$classNameB = 'B' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $classNameB . '{ public $a; public $c; public $integer = "Not an integer"; }');
		$A = new $classNameA();
		$B = new $classNameB();
		$A->b = array($B);
		$B->a = $A;
		$B->c = array($A);

		$this->mockValidatorResolver->expects($this->any())->method('createValidator')->with('Integer')->will($this->returnValue(new \TYPO3\Flow\Validation\Validator\IntegerValidator()));
		$this->mockValidatorResolver->expects($this->any())->method('buildBaseValidatorConjunction')->will($this->returnValue(new \TYPO3\Flow\Validation\Validator\GenericObjectValidator()));

		// Create validators
		$aValidator = new \TYPO3\Flow\Validation\Validator\GenericObjectValidator(array());
		$this->validator->_set('options', array('elementValidator' => 'Integer'));
		$integerValidator = new \TYPO3\Flow\Validation\Validator\IntegerValidator(array());

		// Add validators to properties
		$aValidator->addPropertyValidator('b', $this->validator);
		$aValidator->addPropertyValidator('integer', $integerValidator);

		$result = $aValidator->validate($A)->getFlattenedErrors();
		$this->assertEquals('A valid integer number is expected.', $result['b.0'][0]->getMessage());
	}

	/**
	 * @test
	 */
	public function collectionValidatorIsValidEarlyReturnsOnUnitializedDoctrinePersistenceCollections() {
		$entityManager = $this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', FALSE);
		$collection = new \Doctrine\Common\Collections\ArrayCollection(array());
		$persistentCollection = new \Doctrine\ORM\PersistentCollection($entityManager, '', $collection);
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($persistentCollection, 'initialized', FALSE, TRUE);

		$this->mockValidatorResolver->expects($this->never())->method('createValidator');

		$this->validator->validate($persistentCollection);
	}

}

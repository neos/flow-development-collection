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
 * Testcase for the unique entity validator
 */
class UniqueEntityValidatorTest extends AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\UniqueEntityValidator';

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 * @see \TYPO3\Flow\Reflection\ClassSchema
	 */
	protected $classSchema;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 * @see \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 */
	public function setUp() {
		parent::setUp();
		$this->classSchema = $this->getMock('TYPO3\Flow\Reflection\ClassSchema', array(), array(), '', FALSE);

		$this->reflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$this->reflectionService->expects($this->any())->method('getClassSchema')->will($this->returnValue($this->classSchema));
		$this->inject($this->validator, 'reflectionService', $this->reflectionService);
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionIfValueIsNotAnObject() {
		$this->setExpectedException('TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException', '', 1358454270);
		$this->validator->validate('a string');
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionIfValueIsNotReflectedAtAll() {
		$this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(NULL));

		$this->setExpectedException('TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException', '', 1358454284);
		$this->validator->validate(new \stdClass());
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionIfValueIsNotAFlowEntity() {
		$this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(\TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT));

		$this->setExpectedException('TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException', '', 1358454284);
		$this->validator->validate(new \stdClass());
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionIfSetupPropertiesAreNotPresentInActualClass() {
		$this->prepareMockExpectations();
		$this->inject($this->validator, 'options', array('identityProperties' => array('propertyWhichDoesntExist')));
		$this->classSchema
			->expects($this->once())
			->method('hasProperty')
			->with('propertyWhichDoesntExist')
			->will($this->returnValue(FALSE));

		$this->setExpectedException('TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException', '', 1358960500);
		$this->validator->validate(new \StdClass());
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionIfThereIsNoIdentityProperty() {
		$this->prepareMockExpectations();
		$this->classSchema
			->expects($this->once())
			->method('getIdentityProperties')
			->will($this->returnValue(array()));

		$this->setExpectedException('TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException', '', 1358459831);
		$this->validator->validate(new \StdClass());
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionOnMultipleOrmIdAnnotations() {
		$this->prepareMockExpectations();
		$this->classSchema
			->expects($this->once())
			->method('getIdentityProperties')
			->will($this->returnValue(array('foo')));
		$this->reflectionService
			->expects($this->once())
			->method('getPropertyNamesByAnnotation')
			->with('FooClass', 'Doctrine\ORM\Mapping\Id')
			->will($this->returnValue(array('dummy array', 'with more than', 'one count')));

		$this->setExpectedException('TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException', '', 1358501745);
		$this->validator->validate(new \StdClass());
	}

	/**
	 */
	protected function prepareMockExpectations() {
		$this->classSchema->expects($this->once())->method('getModelType')->will($this->returnValue(\TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_ENTITY));
		$this->classSchema
			->expects($this->any())
			->method('getClassName')
			->will($this->returnValue('FooClass'));
	}


}
?>
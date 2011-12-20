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

require_once 'AbstractValidatorTestcase.php';

/**
 * Testcase for the number range validator
 *
 */
class DateTimeRangeValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	/**
	 * @var string
	 */
	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\DateTimeRangeValidator';

	/**
	 * @var \TYPO3\FLOW3\Validation\Validator\DateTimeRangeValidator
	 */
	protected $accessibleValidator;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->accessibleValidator = $this->getAccessibleMock('TYPO3\FLOW3\Validation\Validator\DateTimeRangeValidator', array('dummy'));
	}

	/**
	 * @test
	 */
	public function parseReferenceDateReturnsInstanceOfDateTime() {
		$testResult = $this->accessibleValidator->_call('parseReferenceDate', '2007-03-01T13:00:00Z/P1Y2M10DT2H30M');
		$this->assertTrue($testResult instanceof \DateTime);
	}

	/**
	 * @test
	 */
	public function parseReferenceDateReturnsTimeWithoutCalculationCorrectly() {
		$testResult = $this->accessibleValidator->_call('parseReferenceDate', '2007-03-01T13:00:00Z');
		$this->assertEquals('2007-03-01 13:00', $testResult->format('Y-m-d H:i'));
	}

	/**
	 * @test
	 */
	public function parseReferenceDateAddsTimeIntervalCorrectlyUsingOnlyHourAndMinute() {
		$testResult = $this->accessibleValidator->_call('parseReferenceDate', '2007-03-01T13:00:00Z/PT2H30M');
		$this->assertEquals('2007-03-01 15:30', $testResult->format('Y-m-d H:i'));
	}

	/**
	 * @test
	 */
	public function parseReferenceDateSubstractsTimeIntervalCorrectlyUsingMonthAndMinuteForcingYearSwap() {
		$testResult = $this->accessibleValidator->_call('parseReferenceDate', 'P4MT15M/2013-02-01T13:00:00Z');
		$this->assertEquals('2012-10-01 12:45', $testResult->format('Y-m-d H:i'));
	}

	/**
	 * @test
	 */
	public function validateReturnsOneErrorIfGivenValueIsNoDate() {
		$this->validatorOptions(array('earliestDate' => '2007-03-01T13:00:00Z'));

		$errors = $this->validator->validate('no DateTime object')->getErrors();
		$this->assertSame(1, count($errors));
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorForAGivenDateMustBeingAfterAFixDate() {
		$this->validatorOptions(array('earliestDate' => '2007-03-01T13:00:00Z'));

		$this->assertFalse($this->validator->validate(new \DateTime('2009-03-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorForAGivenDateMustBeingAfterAFixDate() {
		$this->validatorOptions(array('earliestDate' => '2007-03-01T13:00:00Z'));

		$this->assertTrue($this->validator->validate(new \DateTime('2007-02-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorForAGivenDateMustBeingAfterACalculatedDateRangeViaAdding() {
		$this->validatorOptions(array('earliestDate' => '2007-03-01T13:00:00Z/P1Y2M10DT2H30M'));

		$this->assertFalse($this->validator->validate(new \DateTime('2009-03-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorForAGivenDateMustBeingAfterACalculatedDateRangeViaSubstracting() {
		$this->validatorOptions(array('earliestDate' => 'P2M10DT2H30M/2011-03-01T13:00:00Z'));

		$this->assertTrue($this->validator->validate(new \DateTime('2009-03-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorForAGivenDateMustBeingBeforeACalculatedDateRangeViaAdding() {
		$this->validatorOptions(array('latestDate' => '2007-03-01T13:00:00Z/P1Y2M10DT2H30M'));

		$this->assertFalse($this->validator->validate(new \DateTime('2008-03-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorForAGivenDateMustBeingBeforeACalculatedDateRangeViaSubstracting() {
		$this->validatorOptions(array('latestDate' => 'P2M10DT2H30M/2011-03-01T13:00:00Z'));

		$this->assertTrue($this->validator->validate(new \DateTime('2011-02-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorForAGivenDateOutsideUpperAndLowerBoundaries() {
		$this->validatorOptions(array(
			'earliestDate' => '2011-01-01T13:00:00Z',
			'latestDate' => '2011-03-01T13:00:00Z'
		));

		$this->assertTrue($this->validator->validate(new \DateTime('2011-04-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorForAGivenDateInsideUpperAndLowerBoundaries() {
		$this->validatorOptions(array(
			'earliestDate' => '2011-01-01T13:00:00Z',
			'latestDate' => '2011-03-01T13:00:00Z'
		));

		$this->assertFalse($this->validator->validate(new \DateTime('2011-02-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorForAGivenDateThatIsEqualToTheMinimumDate() {
		$this->validatorOptions(array(
			'earliestDate' => '2011-01-01T13:00:00Z',
		));

		$this->assertFalse($this->validator->validate(new \DateTime('2011-01-01T13:00:00Z'))->hasErrors());
	}

	/**
	 * @test
	 */
	public function validateReturnsNoErrorForAGivenDateThatIsEqualToTheMaximumDate() {
		$this->validatorOptions(array(
			'latestDate' => '2011-01-01T13:00:00Z',
		));

		$this->assertFalse($this->validator->validate(new \DateTime('2011-01-01T13:00:00Z'))->hasErrors());
	}


}

?>
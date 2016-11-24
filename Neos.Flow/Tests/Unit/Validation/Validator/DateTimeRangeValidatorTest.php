<?php
namespace Neos\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Validation\Validator\DateTimeRangeValidator;

require_once 'AbstractValidatorTestcase.php';

/**
 * Testcase for the number range validator
 *
 */
class DateTimeRangeValidatorTest extends AbstractValidatorTestcase
{
    /**
     * @var string
     */
    protected $validatorClassName = DateTimeRangeValidator::class;

    /**
     * @var DateTimeRangeValidator
     */
    protected $accessibleValidator;

    /**
     * Sets up this test case
     */
    public function setUp()
    {
        $this->accessibleValidator = $this->getAccessibleMock(DateTimeRangeValidator::class, ['dummy']);
    }

    /**
     * @test
     */
    public function parseReferenceDateReturnsInstanceOfDateTime()
    {
        $testResult = $this->accessibleValidator->_call('parseReferenceDate', '2007-03-01T13:00:00Z/P1Y2M10DT2H30M');
        $this->assertTrue($testResult instanceof \DateTime);
    }

    /**
     * @test
     */
    public function parseReferenceDateReturnsTimeWithoutCalculationCorrectly()
    {
        $testResult = $this->accessibleValidator->_call('parseReferenceDate', '2007-03-01T13:00:00Z');
        $this->assertEquals('2007-03-01 13:00', $testResult->format('Y-m-d H:i'));
    }

    /**
     * @test
     */
    public function parseReferenceDateAddsTimeIntervalCorrectlyUsingOnlyHourAndMinute()
    {
        $testResult = $this->accessibleValidator->_call('parseReferenceDate', '2007-03-01T13:00:00Z/PT2H30M');
        $this->assertEquals('2007-03-01 15:30', $testResult->format('Y-m-d H:i'));
    }

    /**
     * @test
     */
    public function parseReferenceDateSubstractsTimeIntervalCorrectlyUsingMonthAndMinuteForcingYearSwap()
    {
        $testResult = $this->accessibleValidator->_call('parseReferenceDate', 'P4MT15M/2013-02-01T13:00:00Z');
        $this->assertEquals('2012-10-01 12:45', $testResult->format('Y-m-d H:i'));
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->validatorOptions(['earliestDate' => '2007-03-01T13:00:00Z']);
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->validatorOptions(['earliestDate' => '2007-03-01T13:00:00Z']);
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsOneErrorIfGivenValueIsNoDate()
    {
        $this->validatorOptions(['earliestDate' => '2007-03-01T13:00:00Z']);

        $errors = $this->validator->validate('no DateTime object')->getErrors();
        $this->assertSame(1, count($errors));
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorForAGivenDateMustBeingAfterAFixDate()
    {
        $this->validatorOptions(['earliestDate' => '2007-03-01T13:00:00Z']);

        $this->assertFalse($this->validator->validate(new \DateTime('2009-03-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorForAGivenDateMustBeingAfterAFixDate()
    {
        $this->validatorOptions(['earliestDate' => '2007-03-01T13:00:00Z']);

        $this->assertTrue($this->validator->validate(new \DateTime('2007-02-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorForAGivenDateMustBeingAfterACalculatedDateRangeViaAdding()
    {
        $this->validatorOptions(['earliestDate' => '2007-03-01T13:00:00Z/P1Y2M10DT2H30M']);

        $this->assertFalse($this->validator->validate(new \DateTime('2009-03-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorForAGivenDateMustBeingAfterACalculatedDateRangeViaSubstracting()
    {
        $this->validatorOptions(['earliestDate' => 'P2M10DT2H30M/2011-03-01T13:00:00Z']);

        $this->assertTrue($this->validator->validate(new \DateTime('2009-03-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorForAGivenDateMustBeingBeforeACalculatedDateRangeViaAdding()
    {
        $this->validatorOptions(['latestDate' => '2007-03-01T13:00:00Z/P1Y2M10DT2H30M']);

        $this->assertFalse($this->validator->validate(new \DateTime('2008-03-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorForAGivenDateMustBeingBeforeACalculatedDateRangeViaSubstracting()
    {
        $this->validatorOptions(['latestDate' => 'P2M10DT2H30M/2011-03-01T13:00:00Z']);

        $this->assertTrue($this->validator->validate(new \DateTime('2011-02-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsErrorForAGivenDateOutsideUpperAndLowerBoundaries()
    {
        $this->validatorOptions([
            'earliestDate' => '2011-01-01T13:00:00Z',
            'latestDate' => '2011-03-01T13:00:00Z'
        ]);

        $this->assertTrue($this->validator->validate(new \DateTime('2011-04-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorForAGivenDateInsideUpperAndLowerBoundaries()
    {
        $this->validatorOptions([
            'earliestDate' => '2011-01-01T13:00:00Z',
            'latestDate' => '2011-03-01T13:00:00Z'
        ]);

        $this->assertFalse($this->validator->validate(new \DateTime('2011-02-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorForAGivenDateThatIsEqualToTheMinimumDate()
    {
        $this->validatorOptions([
            'earliestDate' => '2011-01-01T13:00:00Z',
        ]);

        $this->assertFalse($this->validator->validate(new \DateTime('2011-01-01T13:00:00Z'))->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorForAGivenDateThatIsEqualToTheMaximumDate()
    {
        $this->validatorOptions([
            'latestDate' => '2011-01-01T13:00:00Z',
        ]);

        $this->assertFalse($this->validator->validate(new \DateTime('2011-01-01T13:00:00Z'))->hasErrors());
    }
}

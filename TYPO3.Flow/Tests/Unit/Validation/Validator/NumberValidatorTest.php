<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the number validator
 *
 */
class NumberValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = 'TYPO3\Flow\Validation\Validator\NumberValidator';

    /**
     * @var \TYPO3\Flow\I18n\Locale
     */
    protected $sampleLocale;

    protected $numberParser;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en_GB');

        $this->mockNumberParser = $this->createMock('TYPO3\Flow\I18n\Parser\NumberParser');
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        $sampleInvalidNumber = 'this is not a number';

        $this->mockNumberParser->expects($this->once())->method('parseDecimalNumber', $sampleInvalidNumber)->will($this->returnValue(false));

        $this->validatorOptions(array('locale' => $this->sampleLocale));
        $this->inject($this->validator, 'numberParser', $this->mockNumberParser);

        $this->assertEquals(1, count($this->validator->validate($sampleInvalidNumber)->getErrors()));
    }

    /**
     * @test
     */
    public function returnsFalseForIncorrectValues()
    {
        $sampleInvalidNumber = 'this is not a number';

        $this->mockNumberParser->expects($this->once())->method('parsePercentNumber', $sampleInvalidNumber)->will($this->returnValue(false));

        $this->validatorOptions(array('locale' => 'en_GB', 'formatLength' => \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_DEFAULT, 'formatType' => \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_PERCENT));
        $this->inject($this->validator, 'numberParser', $this->mockNumberParser);

        $this->assertEquals(1, count($this->validator->validate($sampleInvalidNumber)->getErrors()));
    }
}

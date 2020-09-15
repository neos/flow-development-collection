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

use Neos\Flow\Validation\Validator\LabelValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the label validator
 *
 */
class LabelValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = LabelValidator::class;

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
     * Data provider with valid labels
     *
     * @return array
     */
    public function validLabels()
    {
        return [
            [''],
            ['The quick brown fox drinks no coffee'],
            ['Kasper Skårhøj doesn\'t like his iPad'],
            ['老 时态等的曲折变化 年代出生的人都会书写常用的繁体汉字事实'],
            ['Где только языках насколько бы, найденных'],
            ['I hope, that the above doesn\'t mean anything harmful'],
            ['Punctuation marks like ,.:;?!%§&"\'/+-_=()# are all allowed'],
            ['Nothing speaks against numbers 0123456789'],
            ['Currencies like £₱௹€$¥ could be important']
        ];
    }

    /**
     * Data provider with invalid labels
     *
     * @return array
     */
    public function invalidLabels()
    {
        return [
            ['<tags> are not allowed'],
            ["\t tabs are not allowed either"],
            ["\n new line? no!"],
            ['☔☃☕ are funny signs, but we don\'t want them in labels'],
        ];
    }

    /**
     * @test
     * @dataProvider validLabels
     */
    public function labelValidatorReturnsNoErrorForValidLabels($label)
    {
        $this->assertFalse($this->validator->validate($label)->hasErrors());
    }

    /**
     * @test
     * @dataProvider invalidLabels
     */
    public function labelValidatorReturnsErrorsForInvalidLabels($label)
    {
        $this->assertTrue($this->validator->validate($label)->hasErrors());
    }
}

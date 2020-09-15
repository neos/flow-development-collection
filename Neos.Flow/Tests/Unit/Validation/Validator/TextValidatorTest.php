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

use Neos\Flow\Validation\Validator\TextValidator;
use Neos\Flow\Validation;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the text validator
 *
 */
class TextValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = TextValidator::class;

    /**
     * @test
     */
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * Data provider with valid input for TextValidator.
     * @return array
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorReturnsNoErrorForASimpleString()
    {
        $this->assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @return array
     */
    public function validInput()
    {
        return [
            ['this is a very simple string'],
            ['Ierd Frot uechter mä get, Kirmesdag Milliounen all en, sinn main Stréi mä och. ' . chr(10) . 'Vu dan durch jéngt gréng, ze rou Monn voll stolz. \nKe kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wär hu Heck Gart zënter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dénen d\'Gaassen eng, eng am virun geplot d\'Lëtzebuerger, get botze rëscht Blieder si. Dat Dauschen schéinste Milliounen fu. Ze riede méngem Keppchen déi, si gét fergiess erwaacht, räich jéngt duerch en nun. Gëtt Gaas d\'Vullen hie hu, laacht Grénge der dé. Gemaacht gehéiert da aus, gutt gudden d\'wäiss mat wa.'],
            ['3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.'],
        ];
    }

    /**
     * @test
     * @dataProvider validInput
     * @param string $input
     */
    public function textValidatorAcceptsValidInput($input)
    {
        $textValidator = new TextValidator();
        $this->assertFalse($textValidator->validate($input)->hasErrors());
    }

    /**
     * Data provider with invalid input for TextValidator.
     * @return array
     */
    public function invalidInput()
    {
        return [
            ['<span style="color: #BBBBBB;">a nice text</span>']
        ];
    }

    /**
     * @test
     * @dataProvider invalidInput
     * @param string $input
     */
    public function textValidatorRejectsInvalidInput($input)
    {
        $this->assertTrue($this->validator->validate($input)->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities()
    {
        $expected = [new Validation\Error('Valid text without any XML tags is expected.', 1221565786)];
        $this->assertEquals($expected, $this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->getErrors());
    }
}

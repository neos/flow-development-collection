<?php

declare(strict_types=1);

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
use Neos\Flow\Validation\Error;
use Neos\Flow\Validation\Validator\TextValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the text validator
 *
 */
final class TextValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = TextValidator::class;

    #[Test]
    public function validateReturnsNoErrorIfTheGivenValueIsNull()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * Data provider with valid input for TextValidator.
     * @return array
     */
    public function validateReturnsNoErrorIfTheGivenValueIsAnEmptyString()
    {
        self::assertFalse($this->validator->validate('')->hasErrors());
    }

    #[Test]
    public function textValidatorReturnsNoErrorForASimpleString()
    {
        self::assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function validInput(): \Iterator
    {
        yield ['this is a very simple string'];
        yield ['Ierd Frot uechter mä get, Kirmesdag Milliounen all en, sinn main Stréi mä och. ' . chr(10) . 'Vu dan durch jéngt gréng, ze rou Monn voll stolz. \nKe kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wär hu Heck Gart zënter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dénen d\'Gaassen eng, eng am virun geplot d\'Lëtzebuerger, get botze rëscht Blieder si. Dat Dauschen schéinste Milliounen fu. Ze riede méngem Keppchen déi, si gét fergiess erwaacht, räich jéngt duerch en nun. Gëtt Gaas d\'Vullen hie hu, laacht Grénge der dé. Gemaacht gehéiert da aus, gutt gudden d\'wäiss mat wa.'];
        yield ['3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.'];
    }

    /**
     * @param string $input
     */
    #[DataProvider('validInput')]
    #[Test]
    public function textValidatorAcceptsValidInput($input)
    {
        $textValidator = new TextValidator();
        self::assertFalse($textValidator->validate($input)->hasErrors());
    }

    /**
     * Data provider with invalid input for TextValidator.
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidInput(): \Iterator
    {
        yield ['<span style="color: #BBBBBB;">a nice text</span>'];
    }

    /**
     * @param string $input
     */
    #[DataProvider('invalidInput')]
    #[Test]
    public function textValidatorRejectsInvalidInput($input)
    {
        self::assertTrue($this->validator->validate($input)->hasErrors());
    }

    #[Test]
    public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities()
    {
        $expected = [new Error('Valid text without any XML tags is expected.', 1221565786)];
        self::assertEquals($expected, $this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->getErrors());
    }
}

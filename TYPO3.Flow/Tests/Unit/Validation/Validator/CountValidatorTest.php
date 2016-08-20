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
 * Testcase for the count validator
 *
 */
class CountValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\CountValidator::class;

    /**
     * @test
     */
    public function countValidatorReturnsNoErrorsIfTheGivenValueIsNull()
    {
        $this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function countValidatorReturnsNoErrorsIfTheGivenStringIsEmpty()
    {
        $this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
        $this->assertFalse($this->validator->validate('')->hasErrors());
    }

    /**
     * @return array
     */
    public function countables()
    {
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach(new \stdClass);
        return array(
            array(array('Foo', 'Bar')),
            array(new \ArrayObject(array('Baz', 'Quux'))),
            array($splObjectStorage)
        );
    }

    /**
     * @test
     * @dataProvider countables
     */
    public function countValidatorReturnsNoErrorsForValidCountables($countable)
    {
        $this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
        $this->assertFalse($this->validator->validate($countable)->hasErrors());
    }

    /**
     * @test
     * @dataProvider countables
     */
    public function countValidatorReturnsErrorsForInvalidCountables($countable)
    {
        $this->validatorOptions(array('minimum' => 5, 'maximum' => 10));
        $this->assertTrue($this->validator->validate($countable)->hasErrors());
    }

    /**
     */
    public function nonCountables()
    {
        $splObjectStorage = new \SplObjectStorage();
        $splObjectStorage->attach(new \stdClass);
        return array(
            array('Bar'),
            array(1),
            array(new \stdClass)
        );
    }

    /**
     * @test
     * @dataProvider nonCountables
     */
    public function countValidatorReturnsErrorsForNonCountables($nonCountable)
    {
        $this->assertTrue($this->validator->validate($nonCountable)->hasErrors());
    }
}

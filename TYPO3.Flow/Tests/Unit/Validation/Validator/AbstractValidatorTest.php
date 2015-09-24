<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the Abstract Validator
 *
 */
class AbstractValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = $this->getAccessibleMockForAbstractClass(\TYPO3\Flow\Validation\Validator\AbstractValidator::class, array(), '', false);
        $this->validator->_set('supportedOptions', array(
            'placeHolder'    => array('default', 'Desc', 'mixed', false),
            'secondPlaceHolder' => array('default', 'Desc', 'mixed'),
            'thirdPlaceHolder'  => array('default', 'Desc', 'mixed', true),
        ));
    }

    /**
     * @test
     */
    public function abstractValidatorConstructWithRequiredOptionShouldNotFail()
    {
        $this->validator->__construct(array( 'thirdPlaceHolder' => 'dummy' ));

        $this->assertInstanceOf(\TYPO3\Flow\Validation\Validator\AbstractValidator::class, $this->validator);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
     */
    public function abstractValidatorConstructWithoutRequiredOptionShouldFail()
    {
        $this->validator->__construct(array());
    }
}

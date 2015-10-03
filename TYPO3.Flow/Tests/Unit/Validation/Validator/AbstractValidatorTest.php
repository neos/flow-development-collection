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

/**
 * Testcase for the Abstract Validator
 *
 */
class AbstractValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = $this->getAccessibleMockForAbstractClass('\TYPO3\Flow\Validation\Validator\AbstractValidator', array(), '', false);
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

        $this->assertInstanceOf('\TYPO3\Flow\Validation\Validator\AbstractValidator', $this->validator);
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

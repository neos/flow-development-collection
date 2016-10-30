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

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Validation\Validator\AbstractValidator;

/**
 * Testcase for the Abstract Validator
 */
class AbstractValidatorTest extends UnitTestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = $this->getAccessibleMockForAbstractClass(AbstractValidator::class, [], '', false);
        $this->validator->_set('supportedOptions', [
            'placeHolder'    => ['default', 'Desc', 'mixed', false],
            'secondPlaceHolder' => ['default', 'Desc', 'mixed'],
            'thirdPlaceHolder'  => ['default', 'Desc', 'mixed', true],
        ]);
    }

    /**
     * @test
     */
    public function abstractValidatorConstructWithRequiredOptionShouldNotFail()
    {
        $this->validator->__construct(['thirdPlaceHolder' => 'dummy']);

        $this->assertInstanceOf(AbstractValidator::class, $this->validator);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
     */
    public function abstractValidatorConstructWithoutRequiredOptionShouldFail()
    {
        $this->validator->__construct([]);
    }
}

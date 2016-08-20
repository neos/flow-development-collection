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
 * Testcase for the raw validator
 *
 */
class RawValidatorTest extends \TYPO3\Flow\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\Flow\Validation\Validator\RawValidator::class;

    /**
     * @test
     */
    public function theRawValidatorAlwaysReturnsNoErrors()
    {
        $rawValidator = new \TYPO3\Flow\Validation\Validator\RawValidator(array());

        $this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
        $this->assertFalse($rawValidator->validate('')->hasErrors());
        $this->assertFalse($rawValidator->validate(null)->hasErrors());
        $this->assertFalse($rawValidator->validate(false)->hasErrors());
        $this->assertFalse($rawValidator->validate(new \ArrayObject())->hasErrors());
    }
}

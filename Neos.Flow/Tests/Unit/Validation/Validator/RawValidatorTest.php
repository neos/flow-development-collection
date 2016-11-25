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

use Neos\Flow\Validation\Validator\RawValidator;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the raw validator
 */
class RawValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = RawValidator::class;

    /**
     * @test
     */
    public function theRawValidatorAlwaysReturnsNoErrors()
    {
        $rawValidator = new RawValidator([]);

        $this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
        $this->assertFalse($rawValidator->validate('')->hasErrors());
        $this->assertFalse($rawValidator->validate(null)->hasErrors());
        $this->assertFalse($rawValidator->validate(false)->hasErrors());
        $this->assertFalse($rawValidator->validate(new \ArrayObject())->hasErrors());
    }
}

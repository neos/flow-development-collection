<?php
namespace Neos\Flow\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Validator for integers.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class IntegerValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid integer.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError('A valid integer number is expected.', 1221560494);
        }
    }
}

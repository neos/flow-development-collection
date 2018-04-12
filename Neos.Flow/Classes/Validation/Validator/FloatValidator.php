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
 * Validator for floats.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class FloatValidator extends AbstractValidator
{
    /**
     * The given value is valid if it is of type float or a string matching the regular expression [0-9.e+-]
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (is_float($value)) {
            return;
        }
        if (!is_string($value) || strpos($value, '.') === false || preg_match('/^[0-9.e+-]+$/', $value) !== 1) {
            $this->addError('A valid float number is expected.', 1221560288);
        }
    }
}

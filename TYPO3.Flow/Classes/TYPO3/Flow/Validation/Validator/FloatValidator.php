<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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

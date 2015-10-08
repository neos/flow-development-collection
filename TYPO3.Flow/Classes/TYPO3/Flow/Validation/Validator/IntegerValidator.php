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

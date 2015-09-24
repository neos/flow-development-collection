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
 * Validator for strings.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class StringValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a string.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_string($value)) {
            $this->addError('A valid string is expected.', 1238108070);
        }
    }
}

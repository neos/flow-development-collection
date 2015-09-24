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
 * Validator for alphanumeric strings.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AlphanumericValidator extends AbstractValidator
{
    /**
     * The given $value is valid if it is an alphanumeric string, which is defined as [[:alnum:]].
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_string($value) || preg_match('/^[[:alnum:]]*$/u', $value) !== 1) {
            $this->addError('Only regular characters (a to z, umlauts, ...) and numbers are allowed.', 1221551320);
        }
    }
}

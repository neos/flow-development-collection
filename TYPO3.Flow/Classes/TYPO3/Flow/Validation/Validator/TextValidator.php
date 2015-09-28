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
 * Validator for "plain" text.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class TextValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid text (contains no XML tags).
     *
     * Be aware that the value of this check entirely depends on the output context.
     * The validated text is not expected to be secure in every circumstance, if you
     * want to be sure of that, use a customized regular expression or filter on output.
     *
     * See http://php.net/filter_var for details.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if ($value !== filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) {
            $this->addError('Valid text without any XML tags is expected.', 1221565786);
        }
    }
}

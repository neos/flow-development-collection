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
 * A validator for locale identifiers.
 *
 * This validator validates a string based on the expressions of the
 * Flow I18n implementation.
 *
 * @Flow\Scope("singleton")
 */
class LocaleIdentifierValidator extends AbstractValidator
{
    /**
     * Is valid if the given value is a valid "locale identifier".
     *
     * @param mixed $value The value that should be validated
     * @return void
     */
    protected function isValid($value)
    {
        if (!preg_match(\TYPO3\Flow\I18n\Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $value)) {
            $this->addError('Value is no valid I18n locale identifier.', 1327090892);
        }
    }
}

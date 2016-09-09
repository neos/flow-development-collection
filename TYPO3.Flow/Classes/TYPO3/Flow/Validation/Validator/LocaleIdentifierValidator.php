<?php
namespace TYPO3\Flow\Validation\Validator;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Locale;

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
        if (!preg_match(Locale::PATTERN_MATCH_LOCALEIDENTIFIER, $value)) {
            $this->addError('Value is no valid I18n locale identifier.', 1327090892);
        }
    }
}

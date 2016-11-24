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
 * Validator for email addresses
 *
 * @api
 * @Flow\Scope("singleton")
 */
class EmailAddressValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid email address.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_string($value) || !$this->validEmail($value)) {
            $this->addError('Please specify a valid email address.', 1221559976);
        }
    }

    /**
     * Checking syntax of input email address
     *
     * @param string $emailAddress Input string to evaluate
     * @return boolean Returns TRUE if the $email address (input string) is valid
     */
    protected function validEmail($emailAddress)
    {
        return (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== false);
    }
}

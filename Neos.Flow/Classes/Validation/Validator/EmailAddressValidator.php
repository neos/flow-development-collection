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

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
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
     * @var array
     */
    protected $supportedOptions = [
        'strict' => [false, 'Whether to fail validation on RFC warnings', 'bool'],
        'checkDns' => [false, 'Whether to use DNS checks', 'bool']
    ];

    /**
     * @var EmailValidator
     */
    protected $emailValidator;

    protected function initializeObject(): void
    {
        $this->emailValidator = new EmailValidator();
    }

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
     * @return bool Returns true if the $email address (input string) is valid
     */
    protected function validEmail($emailAddress): bool
    {
        $rfcValidation = $this->options['strict'] ? new NoRFCWarningsValidation() : new RFCValidation();
        if ($this->options['checkDns']) {
            $mailValidation = new MultipleValidationWithAnd([$rfcValidation, new DNSCheckValidation()]);
        } else {
            $mailValidation = $rfcValidation;
        }

        return $this->emailValidator->isValid($emailAddress, $mailValidation);
    }
}

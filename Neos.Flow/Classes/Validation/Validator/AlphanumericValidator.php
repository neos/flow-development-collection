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

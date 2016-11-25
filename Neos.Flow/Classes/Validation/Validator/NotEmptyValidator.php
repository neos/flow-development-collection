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
 * Validator for not empty values.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class NotEmptyValidator extends AbstractValidator
{
    /**
     * This validator always needs to be executed even if the given value is empty.
     * See AbstractValidator::validate()
     *
     * @var boolean
     */
    protected $acceptsEmptyValues = false;

    /**
     * Checks if the given value is not empty (NULL, empty string, empty array
     * or empty object that implements the Countable interface).
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if ($value === null) {
            $this->addError('This property is required.', 1221560910);
        }
        if ($value === '') {
            $this->addError('This property is required.', 1221560718);
        }
        if (is_array($value) && empty($value)) {
            $this->addError('This property is required', 1354192543);
        }
        if (is_object($value) && $value instanceof \Countable && $value->count() === 0) {
            $this->addError('This property is required.', 1354192552);
        }
    }
}

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

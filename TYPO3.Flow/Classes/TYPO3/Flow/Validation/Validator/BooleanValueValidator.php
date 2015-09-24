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
 * Validator for a specific boolean value.
 *
 * @api
 * @Flow\Scope("prototype")
 */
class BooleanValueValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'expectedValue' => array(true, 'The expected boolean value', 'boolean')
    );

    /**
     * Checks if the given value is a specific boolean value.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if ($value !== $this->options['expectedValue']) {
            $this->addError('The given value is expected to be %1$s.', 1361044943, array($this->options['expectedValue'] ? 'TRUE' : 'FALSE'));
        }
    }
}

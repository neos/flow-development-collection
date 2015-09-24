<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract for a validator
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Constructs the validator and sets validation options
     *
     * @param array $options The validation options
     * @api
     */
    // public function __construct(array $options = array());

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     * @return \TYPO3\Flow\Error\Result
     * @api
     */
    public function validate($value);

    /**
     * Returns the options of this validator which can be specified in the constructor
     *
     * @return array
     */
    public function getOptions();
}

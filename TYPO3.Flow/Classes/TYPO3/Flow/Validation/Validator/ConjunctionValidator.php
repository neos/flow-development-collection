<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * Validator to chain many validators in a conjunction (logical and).
 *
 * @api
 */
class ConjunctionValidator extends AbstractCompositeValidator
{
    /**
     * Checks if the given value is valid according to the validators of the conjunction.
     * Every validator has to be valid, to make the whole conjunction valid.
     *
     * @param mixed $value The value that should be validated
     * @return \TYPO3\Flow\Error\Result
     * @api
     */
    public function validate($value)
    {
        $validators = $this->getValidators();
        if ($validators->count() > 0) {
            $result = null;
            foreach ($validators as $validator) {
                if ($result === null) {
                    $result = $validator->validate($value);
                } else {
                    $result->merge($validator->validate($value));
                }
            }
        } else {
            $result = new \TYPO3\Flow\Error\Result();
        }

        return $result;
    }
}

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
        if ($validators->count() === 0) {
            return new \TYPO3\Flow\Error\Result();
        }

        $result = null;
        foreach ($validators as $validator) {
            if ($result === null) {
                $result = $validator->validate($value);
            } else {
                $result->merge($validator->validate($value));
            }
        }
        return $result;
    }
}

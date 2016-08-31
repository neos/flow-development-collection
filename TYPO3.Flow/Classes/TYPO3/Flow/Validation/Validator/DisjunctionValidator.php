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
 * Validator to chain many validators in a disjunction (logical or).
 *
 * @api
 */
class DisjunctionValidator extends AbstractCompositeValidator
{
    /**
     * Checks if the given value is valid according to the validators of the
     * disjunction.
     *
     * So only one validator has to be valid, to make the whole disjunction valid.
     * Errors are only returned if all validators failed.
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
            $validatorResult = $validator->validate($value);
            if (!$validatorResult->hasErrors()) {
                if ($result === null) {
                    $result = $validatorResult;
                } else {
                    $result->clear();
                }
                return $result;
            }

            if ($result === null) {
                $result = $validatorResult;
            } else {
                $result->merge($validatorResult);
            }
        }
        return $result;
    }
}

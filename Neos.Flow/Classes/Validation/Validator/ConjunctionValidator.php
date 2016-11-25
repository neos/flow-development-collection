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

use Neos\Error\Messages\Result as ErrorResult;

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
     * @return ErrorResult
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
            $result = new ErrorResult();
        }

        return $result;
    }
}

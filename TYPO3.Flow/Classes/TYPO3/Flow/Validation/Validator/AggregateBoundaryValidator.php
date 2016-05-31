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
 * A validator which will not validate Aggregates that are lazy loaded and uninitialized.
 * Validation over Aggregate Boundaries can hence be forced by making the relation to
 * other Aggregate Roots eager loaded.
 *
 * @api
 */
class AggregateBoundaryValidator extends GenericObjectValidator
{
    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred. Will skip validation if value is
     * an uninitialized lazy loading proxy.
     *
     * @param mixed $value The value that should be validated
     * @return \TYPO3\Flow\Error\Result
     * @api
     */
    public function validate($value)
    {
        $this->result = new \TYPO3\Flow\Error\Result();
        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            if (!is_object($value)) {
                $this->addError('Object expected, %1$s given.', 1241099149, array(gettype($value)));
            } elseif ($value instanceof \Doctrine\ORM\Proxy\Proxy && !$value->__isInitialized()) {
                return $this->result;
            } elseif ($this->isValidatedAlready($value) === false) {
                $this->isValid($value);
            }
        }

        return $this->result;
    }
}

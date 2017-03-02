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
use Neos\Error\Messages\Result;

/**
 * A validator which will not validate Aggregates that are lazy loaded and uninitialized.
 * Validation over Aggregate Boundaries can hence be forced by making the relation to
 * other Aggregate Roots eager loaded.
 *
 * Note that this validator is not part of the public API and you should not use it manually.
 */
class AggregateBoundaryValidator extends GenericObjectValidator
{
    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred. Will skip validation if value is
     * an uninitialized lazy loading proxy.
     *
     * @param mixed $value The value that should be validated
     * @return \Neos\Error\Messages\Result
     * @api
     */
    public function validate($value)
    {
        $this->result = new Result();
        /**
         * The idea is that Aggregates form a consistency boundary, and an Aggregate only needs to be
         * validated if it changed state. Also since all entity relations are lazy loaded by default,
         * and the relation will only be initialized when it gets accessed (e.g. during property mapping),
         * we can just skip validation of an uninitialized aggregate.
         * This greatly improves validation performance for domain models with lots of small aggregate
         * relations. Therefore proper Aggregate Design becomes a performance optimization.
         */
        if ($value instanceof \Doctrine\ORM\Proxy\Proxy && !$value->__isInitialized()) {
            return $this->result;
        }
        return parent::validate($value);
    }
}

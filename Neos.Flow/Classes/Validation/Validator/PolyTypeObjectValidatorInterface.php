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

/**
 * Contract for a poly type validator, able to act on possibly any type.
 *
 * @api
 */
interface PolyTypeObjectValidatorInterface extends ObjectValidatorInterface
{
    /**
     * Checks the given target can be validated by the validator implementation.
     *
     * @param mixed $target The object or class name to be checked
     * @return boolean TRUE if the target can be validated
     * @api
     */
    public function canValidate($target);

    /**
     * Return the priority of this validator.
     *
     * Validators with a high priority are chosen before low priority and only one
     * of multiple capable validators will be used.
     *
     * @return integer
     * @api
     */
    public function getPriority();
}

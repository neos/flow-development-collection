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
 * Contract for a validator
 *
 * @api
 */
interface ObjectValidatorInterface extends ValidatorInterface
{
    /**
     * Allows to set a container to keep track of validated instances.
     *
     * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
     * @return void
     * @api
     */
    public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer);
}

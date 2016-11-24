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

use Neos\Flow\Annotations as Flow;

/**
 * A validator which accepts any input.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class RawValidator extends AbstractValidator
{
    /**
     * This validator is always valid.
     *
     * @param mixed $value The value that should be validated (not used here)
     * @return void
     * @api
     */
    public function isValid($value)
    {
    }
}

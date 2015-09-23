<?php
namespace TYPO3\Flow\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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

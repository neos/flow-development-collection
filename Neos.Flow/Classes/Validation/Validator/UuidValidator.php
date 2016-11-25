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
 * Validator for Universally Unique Identifiers.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class UuidValidator extends AbstractValidator
{
    /**
     * A preg pattern to match against UUIDs
     * @var string
     */
    const PATTERN_MATCH_UUID = '/^([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}$/';

    /**
     * Checks if the given value is a syntactically valid UUID.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_string($value) || !preg_match(self::PATTERN_MATCH_UUID, $value)) {
            $this->addError('The given subject was not a valid UUID.', 1221565853);
        }
    }
}

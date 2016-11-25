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
 * A validator for labels.
 *
 * Labels usually allow all kinds of letters, numbers, punctuation marks and
 * the space character. What you don't want in labels though are tabs, new
 * line characters or HTML tags. This validator is for such uses.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LabelValidator extends AbstractValidator
{
    const PATTERN_VALIDCHARACTERS = '/^[\p{L}\p{Sc} ,.:;?!%§&"\'\/+\-_=\(\)#0-9]*$/u';

    /**
     * The given value is valid if it matches the regular expression specified in PATTERN_VALIDCHARACTERS.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (preg_match(self::PATTERN_VALIDCHARACTERS, $value) === 0) {
            $this->addError('Only letters, numbers, spaces and certain punctuation marks are expected.', 1272298003);
        }
    }
}

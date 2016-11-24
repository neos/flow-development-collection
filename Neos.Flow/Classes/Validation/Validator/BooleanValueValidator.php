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
 * Validator for a specific boolean value.
 *
 * @api
 * @Flow\Scope("prototype")
 */
class BooleanValueValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'expectedValue' => [true, 'The expected boolean value', 'boolean']
    ];

    /**
     * Checks if the given value is a specific boolean value.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if ($value !== $this->options['expectedValue']) {
            $this->addError('The given value is expected to be %1$s.', 1361044943, [$this->options['expectedValue'] ? 'TRUE' : 'FALSE']);
        }
    }
}

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
 * Validator for countable things
 *
 * @api
 */
class CountValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => [0, 'The minimum count to accept', 'integer'],
        'maximum' => [PHP_INT_MAX, 'The maximum count to accept', 'integer']
    ];

    /**
     * The given value is valid if it is an array or \Countable that contains the specified amount of elements.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_array($value) && !($value instanceof \Countable)) {
            $this->addError('The given subject was not countable.', 1253718666);
            return;
        }

        $minimum = intval($this->options['minimum']);
        $maximum = intval($this->options['maximum']);
        if (count($value) < $minimum || count($value) > $maximum) {
            $this->addError('The count must be between %1$d and %2$d.', 1253718831, [$minimum, $maximum]);
        }
    }
}

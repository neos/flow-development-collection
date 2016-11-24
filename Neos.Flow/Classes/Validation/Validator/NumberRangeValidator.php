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
 * Validator for general numbers
 *
 * @api
 */
class NumberRangeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => [0, 'The minimum value to accept', 'integer'],
        'maximum' => [PHP_INT_MAX, 'The maximum value to accept', 'integer']
    ];

    /**
     * The given value is valid if it is a number in the specified range.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_numeric($value)) {
            $this->addError('A valid number is expected.', 1221563685);
            return;
        }

        $minimum = $this->options['minimum'];
        $maximum = $this->options['maximum'];
        if ($minimum > $maximum) {
            $x = $minimum;
            $minimum = $maximum;
            $maximum = $x;
        }
        if ($value < $minimum || $value > $maximum) {
            $this->addError('Please enter a valid number between %1$d and %2$d.', 1221561046, [$minimum, $maximum]);
        }
    }
}

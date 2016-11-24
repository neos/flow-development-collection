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

use Neos\Error\Messages\Result as ErrorResult;

/**
 * Contract for a validator
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Constructs the validator and sets validation options
     *
     * @param array $options The validation options
     * @api
     */
    // public function __construct(array $options = array());

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     * @return ErrorResult
     * @api
     */
    public function validate($value);

    /**
     * Returns the options of this validator which can be specified in the constructor
     *
     * @return array
     */
    public function getOptions();
}

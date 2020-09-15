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

use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Error\Messages\Result as ErrorResult;
use Neos\Flow\Validation\Error as ValidationError;

/**
 * Abstract validator
 *
 * @api
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Specifies whether this validator accepts empty values.
     *
     * If this is true, the validators isValid() method is not called in case of an empty value
     * Note: A value is considered empty if it is NULL or an empty string!
     * By default all validators except for NotEmpty and the Composite Validators accept empty values
     *
     * @var boolean
     */
    protected $acceptsEmptyValues = true;

    /**
     * This contains the supported options, each being an array of:
     *
     * 0 => default value
     * 1 => description
     * 2 => type
     * 3 => required (boolean, optional)
     *
     * @var array
     */
    protected $supportedOptions = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @deprecated since Flow 4.3. Don't overwrite this and instead use pushResult/popResult
     * @var ErrorResult
     */
    protected $result;

    /**
     * @var array<ErrorResult>
     */
    protected $resultStack;

    /**
     * Constructs the validator and sets validation options
     *
     * @param array $options Options for the validator
     * @throws InvalidValidationOptionsException if unsupported options are found
     * @api
     */
    public function __construct(array $options = [])
    {
        // check for options given but not supported
        if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== []) {
            throw new InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1339079393);
        }

        // check for required options being set
        array_walk(
            $this->supportedOptions,
            function ($supportedOptionData, $supportedOptionName, $options) {
                if (array_key_exists(3, $supportedOptionData) && $supportedOptionData[3] === true && !array_key_exists($supportedOptionName, $options)) {
                    throw new InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163902);
                }
            },
            $options
        );

        // merge with default values
        $this->options = array_merge(
            array_map(
                function ($value) {
                    return $value[0];
                },
                $this->supportedOptions
            ),
            $options
        );

        $this->resultStack = [];
    }

    /**
     * Push a new Result onto the Result stack and return it in order to fix cyclic calls to a single validator.
     * @since Flow 4.3
     * @see https://github.com/neos/flow-development-collection/pull/1275#issuecomment-414052031
     * @return ErrorResult
     */
    protected function pushResult()
    {
        if ($this->result !== null) {
            array_push($this->resultStack, $this->result);
        }
        $this->result = new ErrorResult();
        return $this->result;
    }

    /**
     * Pop and return the current Result from the stack and make $this->result point to the last Result again.
     * @since Flow 4.3
     * @return ErrorResult
     */
    protected function popResult()
    {
        $result = $this->result;
        $this->result = array_pop($this->resultStack);
        return $result;
    }

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     * @return ErrorResult
     * @api
     */
    public function validate($value)
    {
        $this->pushResult();
        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            $this->isValid($value);
        }
        return $this->popResult();
    }

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to Result.
     *
     * @param mixed $value
     * @return void
     * @throws InvalidValidationOptionsException if invalid validation options have been specified in the constructor
     */
    abstract protected function isValid($value);

    /**
     * Creates a new validation error object and adds it to $this->errors
     *
     * @param string $message The error message
     * @param integer $code The error code (a unix timestamp)
     * @param array $arguments Arguments to be replaced in message
     * @return void
     * @api
     */
    protected function addError($message, $code, array $arguments = [])
    {
        $this->result->addError(new ValidationError($message, $code, $arguments));
    }

    /**
     * Returns the options of this validator
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $value
     * @return boolean true if the given $value is NULL or an empty string ('')
     */
    final protected function isEmpty($value)
    {
        return $value === null || $value === '';
    }
}

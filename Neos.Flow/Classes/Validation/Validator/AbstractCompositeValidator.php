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
use Neos\Flow\Validation\Exception\NoSuchValidatorException;

/**
 * An abstract composite validator consisting of other validators
 *
 * @api
 */
abstract class AbstractCompositeValidator implements ObjectValidatorInterface, \Countable
{
    /**
     * This contains the supported options, their default values and descriptions.
     *
     * @var array
     */
    protected $supportedOptions = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \SplObjectStorage
     */
    protected $validators;

    /**
     * @var \SplObjectStorage
     */
    protected $validatedInstancesContainer;

    /**
     * Constructs the composite validator and sets validation options
     *
     * @param array $options Options for the validator
     * @api
     * @throws InvalidValidationOptionsException
     */
    public function __construct(array $options = [])
    {
        // check for options given but not supported
        if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== []) {
            throw new InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1339079804);
        }

        // check for required options being set
        array_walk(
            $this->supportedOptions,
            function ($supportedOptionData, $supportedOptionName, $options) {
                if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
                    throw new InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163922);
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
        $this->validators = new \SplObjectStorage();
    }

    /**
     * Allows to set a container to keep track of validated instances.
     *
     * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
     * @return void
     * @api
     */
    public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer)
    {
        $this->validatedInstancesContainer = $validatedInstancesContainer;
    }

    /**
     * Adds a new validator to the conjunction.
     *
     * @param ValidatorInterface $validator The validator that should be added
     * @return void
     * @api
     */
    public function addValidator(ValidatorInterface $validator)
    {
        if ($validator instanceof ObjectValidatorInterface) {
            $validator->setValidatedInstancesContainer = $this->validatedInstancesContainer;
        }
        $this->validators->attach($validator);
    }

    /**
     * Removes the specified validator.
     *
     * @param ValidatorInterface $validator The validator to remove
     * @throws NoSuchValidatorException
     * @api
     */
    public function removeValidator(ValidatorInterface $validator)
    {
        if (!$this->validators->contains($validator)) {
            throw new NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
        }
        $this->validators->detach($validator);
    }

    /**
     * Returns the number of validators contained in this conjunction.
     *
     * @return integer The number of validators
     * @api
     */
    public function count()
    {
        return count($this->validators);
    }

    /**
     * Returns the child validators of this Composite Validator
     *
     * @return \SplObjectStorage
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * Returns the options for this validator
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}

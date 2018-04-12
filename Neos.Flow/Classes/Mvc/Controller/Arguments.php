<?php
namespace Neos\Flow\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;

/**
 * A composite of controller arguments
 *
 * @api
 */
class Arguments extends \ArrayObject
{
    /**
     * Names of the arguments contained by this object
     * @var array
     */
    protected $argumentNames = [];

    /**
     * Adds or replaces the argument specified by $value. The argument's name is taken from the
     * argument object itself, therefore the $offset does not have any meaning in this context.
     *
     * @param mixed $offset Offset - not used here
     * @param mixed $value The argument.
     * @return void
     * @throws \InvalidArgumentException if the argument is not a valid Controller Argument object
     * @api
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof Argument) {
            throw new \InvalidArgumentException(sprintf('Controller arguments must be valid %s objects.', Argument::class), 1187953786);
        }

        $argumentName = $value->getName();
        parent::offsetSet($argumentName, $value);
        $this->argumentNames[$argumentName] = true;
    }

    /**
     * Sets an argument, aliased to offsetSet()
     *
     * @param mixed $value The value
     * @return void
     * @throws \InvalidArgumentException if the argument is not a valid Controller Argument object
     * @api
     */
    public function append($value)
    {
        if (!$value instanceof Argument) {
            throw new \InvalidArgumentException(sprintf('Controller arguments must be valid %s objects.', Argument::class), 1187953786);
        }
        $this->offsetSet(null, $value);
    }

    /**
     * Unsets an argument
     *
     * @param mixed $offset Offset
     * @return void
     * @api
     */
    public function offsetUnset($offset)
    {
        $translatedOffset = $this->validateArgumentExistence($offset);
        parent::offsetUnset($translatedOffset);

        unset($this->argumentNames[$translatedOffset]);
        if ($offset != $translatedOffset) {
            unset($this->argumentShortNames[$offset]);
        }
    }

    /**
     * Returns whether the requested index exists
     *
     * @param mixed $offset Offset
     * @return boolean
     * @api
     */
    public function offsetExists($offset)
    {
        $translatedOffset = $this->validateArgumentExistence($offset);
        return parent::offsetExists($translatedOffset);
    }

    /**
     * Returns the value at the specified index
     *
     * @param mixed $offset Offset
     * @return Argument The requested argument object
     * @throws NoSuchArgumentException if the argument does not exist
     * @api
     */
    public function offsetGet($offset)
    {
        $translatedOffset = $this->validateArgumentExistence($offset);
        if ($translatedOffset === false) {
            throw new \Neos\Flow\Mvc\Exception\NoSuchArgumentException('An argument "' . $offset . '" does not exist.', 1216909923);
        }
        return parent::offsetGet($translatedOffset);
    }

    /**
     * Creates, adds and returns a new controller argument to this composite object.
     * If an argument with the same name exists already, it will be replaced by the
     * new argument object.
     *
     * @param string $name Name of the argument
     * @param string $dataType Name of one of the built-in data types
     * @param boolean $isRequired TRUE if this argument should be marked as required
     * @param mixed $defaultValue Default value of the argument. Only makes sense if $isRequired==FALSE
     * @return Argument The new argument
     * @api
     */
    public function addNewArgument($name, $dataType = 'string', $isRequired = true, $defaultValue = null)
    {
        $argument = new Argument($name, $dataType);
        $argument->setRequired($isRequired);
        $argument->setDefaultValue($defaultValue);

        $this->addArgument($argument);
        return $argument;
    }

    /**
     * Adds the specified controller argument to this composite object.
     * If an argument with the same name exists already, it will be replaced by the
     * new argument object.
     *
     * Note that the argument will be cloned, not referenced.
     *
     * @param Argument $argument The argument to add
     * @return void
     * @api
     */
    public function addArgument(Argument $argument)
    {
        $this->offsetSet(null, $argument);
    }

    /**
     * Returns an argument specified by name
     *
     * @param string $argumentName Name of the argument to retrieve
     * @return Argument
     * @throws NoSuchArgumentException
     * @api
     */
    public function getArgument($argumentName)
    {
        return $this->offsetGet($argumentName);
    }

    /**
     * Checks if an argument with the specified name exists
     *
     * @param string $argumentName Name of the argument to check for
     * @return boolean TRUE if such an argument exists, otherwise FALSE
     * @see offsetExists()
     * @api
     */
    public function hasArgument($argumentName)
    {
        return $this->offsetExists($argumentName);
    }

    /**
     * Returns the names of all arguments contained in this object
     *
     * @return array Argument names
     * @api
     */
    public function getArgumentNames()
    {
        return array_keys($this->argumentNames);
    }

    /**
     * Magic setter method for the argument values. Each argument
     * value can be set by just calling the setArgumentName() method.
     *
     * @param string $methodName Name of the method
     * @param array $arguments Method arguments
     * @return void
     * @throws \LogicException
     */
    public function __call($methodName, array $arguments)
    {
        if (substr($methodName, 0, 3) !== 'set') {
            throw new \LogicException('Unknown method "' . $methodName . '".', 1210858451);
        }
        $firstLowerCaseArgumentName = $this->validateArgumentExistence(strtolower($methodName[3]) . substr($methodName, 4));
        $firstUpperCaseArgumentName = $this->validateArgumentExistence(ucfirst(substr($methodName, 3)));

        if (in_array($firstLowerCaseArgumentName, $this->getArgumentNames())) {
            $argument = parent::offsetGet($firstLowerCaseArgumentName);
            $argument->setValue($arguments[0]);
        } elseif (in_array($firstUpperCaseArgumentName, $this->getArgumentNames())) {
            $argument = parent::offsetGet($firstUpperCaseArgumentName);
            $argument->setValue($arguments[0]);
        }
    }

    /**
     * Translates a short argument name to its corresponding long name. If the
     * specified argument name is a real argument name already, it will be returned again.
     *
     * If an argument with the specified name or short name does not exist, an empty
     * string is returned.
     *
     * @param string $argumentName argument name
     * @return string long argument name or empty string
     */
    protected function validateArgumentExistence($argumentName)
    {
        if (in_array($argumentName, $this->getArgumentNames())) {
            return $argumentName;
        }

        return false;
    }

    /**
     * Remove all arguments and resets this object
     *
     * @return void
     */
    public function removeAll()
    {
        foreach ($this->argumentNames as $argumentName => $booleanValue) {
            parent::offsetUnset($argumentName);
        }
        $this->argumentNames = [];
    }

    /**
     * Get all property mapping / validation errors
     *
     * @return Result
     */
    public function getValidationResults()
    {
        $results = new Result();

        foreach ($this as $argument) {
            $argumentValidationResults = $argument->getValidationResults();
            if ($argumentValidationResults === null) {
                continue;
            }

            $results
                ->forProperty($argument->getName())
                ->merge($argumentValidationResults);
        }

        return $results;
    }
}

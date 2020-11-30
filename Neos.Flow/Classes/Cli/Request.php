<?php
namespace Neos\Flow\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Exception\InvalidArgumentNameException;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;

/**
 * Represents a CLI request.
 *
 * @api
 */
class Request
{
    /**
     * @var string
     */
    protected $controllerObjectName;

    /**
     * @var string
     */
    protected $controllerCommandName = 'default';

    /**
     * @var Command
     */
    protected $command;

    /**
     * The arguments for this request
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $exceedingArguments = [];

    /**
     * If this request has been changed and needs to be dispatched again
     * @var boolean
     */
    protected $dispatched = false;

    /**
     * Sets the dispatched flag
     *
     * @param boolean $flag If this request has been dispatched
     * @return void
     */
    public function setDispatched($flag)
    {
        $this->dispatched = $flag ? true : false;
    }

    /**
     * If this request has been dispatched and addressed by the responsible
     * controller and the response is ready to be sent.
     *
     * The dispatcher will try to dispatch the request again if it has not been
     * addressed yet.
     *
     * @return boolean true if this request has been dispatched successfully
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * Sets the object name of the controller
     *
     * @param string $controllerObjectName Object name of the controller which processes this request
     * @return void
     */
    public function setControllerObjectName(string $controllerObjectName)
    {
        $this->controllerObjectName = $controllerObjectName;
        $this->command = null;
    }

    /**
     * Returns the object name of the controller
     *
     * @return string The controller's object name
     */
    public function getControllerObjectName(): string
    {
        return $this->controllerObjectName;
    }

    /**
     * Sets the name of the command contained in this request.
     *
     * Note that the command name must start with a lower case letter and is case sensitive.
     *
     * @param string $commandName Name of the command to execute by the controller
     * @return void
     */
    public function setControllerCommandName(string $commandName)
    {
        $this->controllerCommandName = $commandName;
        $this->command = null;
    }

    /**
     * Returns the name of the command the controller is supposed to execute.
     *
     * @return string Command name
     */
    public function getControllerCommandName(): string
    {
        return $this->controllerCommandName;
    }

    /**
     * Returns the command object for this request
     *
     * @return Command
     */
    public function getCommand(): Command
    {
        if ($this->command === null) {
            $this->command = new Command($this->controllerObjectName, $this->controllerCommandName);
        }
        return $this->command;
    }

    /**
     * Sets the value of the specified argument
     *
     * @param string $argumentName Name of the argument to set
     * @param mixed $value The new value
     * @return void
     * @throws InvalidArgumentNameException
     */
    public function setArgument(string $argumentName, $value): void
    {
        if ($argumentName === '') {
            throw new InvalidArgumentNameException('Invalid argument name.', 1300893885);
        }
        $this->arguments[$argumentName] = $value;
    }

    /**
     * Sets the whole arguments array and therefore replaces any arguments which existed before.
     *
     * @param array $arguments An array of argument names and their values
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Returns the value of the specified argument
     *
     * @param string $argumentName Name of the argument
     * @return mixed Value of the argument
     * @throws NoSuchArgumentException if such an argument does not exist
     */
    public function getArgument(string $argumentName)
    {
        if (!isset($this->arguments[$argumentName])) {
            throw new NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1300893886);
        }
        return $this->arguments[$argumentName];
    }

    /**
     * Checks if an argument of the given name exists (is set)
     *
     * @param string $argumentName Name of the argument to check
     * @return boolean true if the argument is set, otherwise false
     */
    public function hasArgument(string $argumentName): bool
    {
        return isset($this->arguments[$argumentName]);
    }

    /**
     * Returns an ArrayObject of arguments and their values
     *
     * @return array Array of arguments and their values (which may be arguments and values as well)
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Sets the exceeding arguments
     *
     * @param array $exceedingArguments Numeric array of exceeding arguments
     * @return void
     */
    public function setExceedingArguments(array $exceedingArguments)
    {
        $this->exceedingArguments = $exceedingArguments;
    }

    /**
     * Returns additional unnamed arguments (if any) which have been passed through the command line after all
     * required arguments (if any) have been specified.
     *
     * For a command method with the signature ($argument1, $argument2) and for the command line
     * ./flow acme:foo --argument1 Foo --argument2 Bar baz quux
     * this method would return array(0 => 'baz', 1 => 'quux')
     *
     * @return array Numeric array of exceeding argument values
     */
    public function getExceedingArguments(): array
    {
        return $this->exceedingArguments;
    }
}

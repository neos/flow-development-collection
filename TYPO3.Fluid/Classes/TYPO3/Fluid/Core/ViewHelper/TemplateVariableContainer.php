<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\ViewHelper\Exception\InvalidVariableException;

/**
 * VariableContainer which stores template variables.
 * Is used in two contexts:
 *
 * 1) Holds the current variables in the template
 * 2) Holds variables being set during Parsing (set in view helpers implementing the PostParse facet)
 *
 * @api
 */
class TemplateVariableContainer implements \ArrayAccess
{
    /**
     * List of reserved words that can't be used as variable identifiers in Fluid templates
     *
     * @var array
     */
    protected static $reservedVariableNames = array('true', 'false', 'on', 'off', 'yes', 'no', '_all');

    /**
     * Variables stored in context
     *
     * @var array
     */
    protected $variables = array();

    /**
     * Constructor. Can take an array, and initializes the variables with it.
     *
     * @param array $variableArray
     * @api
     */
    public function __construct(array $variableArray = array())
    {
        $this->variables = $variableArray;
    }

    /**
     * Add a variable to the context
     *
     * @param string $identifier Identifier of the variable to add
     * @param mixed $value The variable's value
     * @return void
     * @throws Exception\InvalidVariableException
     * @api
     */
    public function add($identifier, $value)
    {
        if (array_key_exists($identifier, $this->variables)) {
            throw new InvalidVariableException('Duplicate variable declaration, "' . $identifier . '" already set!', 1224479063);
        }
        if (in_array(strtolower($identifier), self::$reservedVariableNames)) {
            throw new InvalidVariableException('"' . $identifier . '" is a reserved variable name and cannot be used as variable identifier.', 1256730379);
        }
        $this->variables[$identifier] = $value;
    }

    /**
     * Get a variable from the context. Throws exception if variable is not found in context.
     *
     * If "_all" is given as identifier, all variables are returned in an array,
     * if one of the other reserved variables are given, their appropriate value
     * they're representing is returned.
     *
     * @param string $identifier
     * @return mixed The variable value identified by $identifier
     * @throws Exception\InvalidVariableException
     * @api
     */
    public function get($identifier)
    {
        switch ($identifier) {
            case '_all':
                return $this->variables;

            case 'true':
            case 'on':
            case 'yes':
                return true;

            case 'false':
            case 'off':
            case 'no':
                return false;
        }

        if (!array_key_exists($identifier, $this->variables)) {
            throw new InvalidVariableException('Tried to get a variable "' . $identifier . '" which is not stored in the context!', 1224479370);
        }
        return $this->variables[$identifier];
    }

    /**
     * Remove a variable from context. Throws exception if variable is not found in context.
     *
     * @param string $identifier The identifier to remove
     * @return void
     * @throws Exception\InvalidVariableException
     * @api
     */
    public function remove($identifier)
    {
        if (!array_key_exists($identifier, $this->variables)) {
            throw new InvalidVariableException('Tried to remove a variable "' . $identifier . '" which is not stored in the context!', 1224479372);
        }
        unset($this->variables[$identifier]);
    }

    /**
     * Returns an array of all identifiers available in the context.
     *
     * @return array Array of identifier strings
     */
    public function getAllIdentifiers()
    {
        return array_keys($this->variables);
    }

    /**
     * Returns the variables array.
     *
     * @return array Identifiers and values of all variables
     */
    public function getAll()
    {
        return $this->variables;
    }

    /**
     * Checks if this property exists in the VariableContainer.
     *
     * @param string $identifier
     * @return boolean TRUE if $identifier exists, FALSE otherwise
     * @api
     */
    public function exists($identifier)
    {
        if (in_array($identifier, self::$reservedVariableNames, true)) {
            return true;
        }

        return array_key_exists($identifier, $this->variables);
    }

    /**
     * Clean up for serializing.
     *
     * @return array
     */
    public function __sleep()
    {
        return array('variables');
    }

    /**
     * Adds a variable to the context.
     *
     * @param string $identifier Identifier of the variable to add
     * @param mixed $value The variable's value
     * @return void
     */
    public function offsetSet($identifier, $value)
    {
        $this->add($identifier, $value);
    }

    /**
     * Remove a variable from context. Throws exception if variable is not found in context.
     *
     * @param string $identifier The identifier to remove
     * @return void
     */
    public function offsetUnset($identifier)
    {
        $this->remove($identifier);
    }

    /**
     * Checks if this property exists in the VariableContainer.
     *
     * @param string $identifier
     * @return boolean TRUE if $identifier exists, FALSE otherwise
     */
    public function offsetExists($identifier)
    {
        return $this->exists($identifier);
    }

    /**
     * Get a variable from the context. Throws exception if variable is not found in context.
     *
     * @param string $identifier
     * @return mixed The variable identified by $identifier
     */
    public function offsetGet($identifier)
    {
        return $this->get($identifier);
    }

    /**
     * Gets a variable or NULL if it does not exist
     *
     * @param string $variableName name of the variable
     * @return mixed the stored variable or NULL
     */
    public function getOrNull($variableName)
    {
        if ($variableName === '_all') {
            return $this->variables;
        }

        return isset($this->variables[$variableName]) ? $this->variables[$variableName] : null;
    }
}

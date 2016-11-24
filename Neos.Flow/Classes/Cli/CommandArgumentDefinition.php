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

/**
 * Represents a CommandArgumentDefinition
 *
 */
class CommandArgumentDefinition
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var boolean
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * Constructor
     *
     * @param string $name name of the command argument (= parameter name)
     * @param boolean $required defines whether this argument is required or optional
     * @param string $description description of the argument
     */
    public function __construct($name, $required, $description)
    {
        $this->name = $name;
        $this->required = $required;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the lowercased name with dashes as word separator
     *
     * @return string
     */
    public function getDashedName()
    {
        $dashedName = ucfirst($this->name);
        $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
        return '--' . strtolower(substr($dashedName, 0, -1));
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function isRequired()
    {
        return $this->required;
    }
}

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
    public function __construct(string $name, bool $required, string $description)
    {
        $this->name = $name;
        $this->required = $required;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the lowercased name with dashes as word separator
     *
     * @return string
     */
    public function getDashedName(): string
    {
        $dashedName = ucfirst($this->name);
        $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
        return '--' . strtolower(substr($dashedName, 0, -1));
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }
}

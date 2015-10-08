<?php
namespace TYPO3\Flow\Security\Authentication\EntryPoint;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An abstract authentication entry point.
 */
abstract class AbstractEntryPoint implements \TYPO3\Flow\Security\Authentication\EntryPointInterface
{
    /**
     * The configurations options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Sets the options array
     *
     * @param array $options An array of configuration options
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Returns the options array
     *
     * @return array The configuration options of this entry point
     */
    public function getOptions()
    {
        return $this->options;
    }
}

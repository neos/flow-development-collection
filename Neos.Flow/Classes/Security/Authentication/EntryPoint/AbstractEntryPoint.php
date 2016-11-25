<?php
namespace Neos\Flow\Security\Authentication\EntryPoint;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authentication\EntryPointInterface;

/**
 * An abstract authentication entry point.
 */
abstract class AbstractEntryPoint implements EntryPointInterface
{
    /**
     * The configurations options
     *
     * @var array
     */
    protected $options = [];

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

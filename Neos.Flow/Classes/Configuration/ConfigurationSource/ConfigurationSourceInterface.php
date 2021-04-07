<?php
namespace Neos\Flow\Configuration\ConfigurationSource;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\ApplicationContext;

/**
 * The interface for a configuration source
 */
interface ConfigurationSourceInterface
{
    /**
     * The name of this configuration type (one of the ConfigurationManager::CONFIGURATION_TYPE_* constants or a custom configuration type name like "NodeTypes")
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Read configuration resources and return the final configuration array for the given configurationType
     *
     * @param array $packages An array of Package objects (indexed by package key) to consider
     * @param ApplicationContext $context
     * @return array The Configuration array for the current configurationType
     */
    public function process(array $packages, ApplicationContext $context) : array;
}

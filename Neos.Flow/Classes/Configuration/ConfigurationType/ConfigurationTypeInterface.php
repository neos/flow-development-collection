<?php
namespace Neos\Flow\Configuration\ConfigurationType;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;

/**
 * The interface for a configuration type processor
 */
interface ConfigurationTypeInterface
{
    /**
     * Check allowSplitSource for the configuration type.
     *
     * @param string $configurationType
     * @return boolean
     */
    public function isSplitSourceAllowedForConfigurationType(string $configurationType): bool;

    /**
     * @param ApplicationContext $context
     * @return $this
     */
    public function setApplicationContext(ApplicationContext $context) : self;

    /**
     * @param ConfigurationManager $configurationManager
     * @return $this
     */
    public function setConfigurationManager(ConfigurationManager $configurationManager) : self;

    /**
     * Read configuration resources and return the final configuration array for the given configurationType
     *
     * @param YamlSource $configurationSource
     * @param string $configurationType The type name this instance was registered for, may be anything
     * @param array $packages An array of Package objects (indexed by package key) to consider
     * @param array $currentConfig The current
     * @return array The Configuration array for the current configurationType
     */
    public function process(YamlSource $configurationSource, string $configurationType, array $packages, array $currentConfig) : array;
}

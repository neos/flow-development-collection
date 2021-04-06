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
 * Abstract implementation for a configurationType
 */
abstract class AbstractConfigurationType implements ConfigurationTypeInterface
{
    /**
     * The application context of the configuration to manage
     *
     * @var ApplicationContext
     */
    protected $context;

    /**
     * The configuration manager used for this configuration type
     *
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * An array of context name strings, from the most generic one to the most special one.
     * Example:
     * Development, Development/Foo, Development/Foo/Bar
     *
     * @var array
     */
    protected $orderedListOfContextNames = [];

    /**
     * @param ApplicationContext $context The application context to fetch configuration for
     * @return $this
     */
    public function setApplicationContext(ApplicationContext $context) : self
    {
        $this->context = $context;

        $orderedListOfContextNames = [];
        $currentContext = $context;
        do {
            $orderedListOfContextNames[] = (string)$currentContext;
        } while ($currentContext = $currentContext->getParent());
        $this->orderedListOfContextNames = array_reverse($orderedListOfContextNames);

        return $this;
    }

    /**
     * @param ConfigurationManager $configurationManager
     * @return $this
     */
    public function setConfigurationManager(ConfigurationManager $configurationManager) : self
    {
        $this->configurationManager = $configurationManager;
        return $this;
    }

    /**
     * Check allowSplitSource for the configuration type.
     *
     * @param string $configurationType
     * @return boolean
     */
    abstract public function isSplitSourceAllowedForConfigurationType(string $configurationType): bool;

    abstract public function process(YamlSource $configurationSource, string $configurationType, array $packages, array $currentConfig) : array;
}

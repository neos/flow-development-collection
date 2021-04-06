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
use Neos\Flow\Configuration\RouteConfigurationProcessor;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\I18n\Configuration;
use Neos\Utility\Arrays;

/**
 * Class Loader implementation as fallback to the compoer loader and for test classes.
 *
 */
class RoutesConfigurationType extends AbstractConfigurationType
{
    /**
     * Check allowSplitSource for the configuration type.
     *
     * @param string $configurationType
     * @return boolean
     */
    public function isSplitSourceAllowedForConfigurationType(string $configurationType): bool
    {
        return false;
    }

    /**
     *
     * @return array
     */
    public function process(YamlSource $configurationSource, string $configurationType, array $packages, array $configuration) : array
    {
        $allowSplitSource = $this->isSplitSourceAllowedForConfigurationType($configurationType);

        // load main routes
        foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
            $configuration = array_merge($configuration, $configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
        }
        $configuration = array_merge($configuration, $configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));
        $routeProcessor = new RouteConfigurationProcessor(
            ($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.mvc.routes') ?? []),
            $this->orderedListOfContextNames,
            $packages,
            $configurationSource
        );
        $configuration = $routeProcessor->process($configuration);

        return $configuration;
    }
}

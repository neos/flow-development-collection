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

use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Utility\Arrays;

/**
 * Class Loader implementation as fallback to the compoer loader and for test classes.
 *
 */
class DefaultConfigurationType extends AbstractConfigurationType
{
    /**
     * Check allowSplitSource for the configuration type.
     *
     * @param string $configurationType
     * @return boolean
     */
    public function isSplitSourceAllowedForConfigurationType(string $configurationType): bool
    {
        return true;
    }

    /**
     *
     *
     * @return array
     */
    public function process(YamlSource $configurationSource, string $configurationType, array $packages, array $configuration) : array
    {
        $allowSplitSource = $this->isSplitSourceAllowedForConfigurationType($configurationType);

        foreach ($packages as $package) {
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
        }
        $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

        foreach ($this->orderedListOfContextNames as $contextName) {
            foreach ($packages as $package) {
                $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource));
            }
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
        }

        return $configuration;
    }
}

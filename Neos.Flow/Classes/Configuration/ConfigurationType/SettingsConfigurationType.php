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
class SettingsConfigurationType extends AbstractConfigurationType
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
     * @return array
     */
    public function process(YamlSource $configurationSource, string $configurationType, array $packages, array $configuration) : array
    {
        $allowSplitSource = $this->isSplitSourceAllowedForConfigurationType($configurationType);

        // Make sure that the Flow package is the first item of the packages array:
        if (isset($packages['Neos.Flow'])) {
            $flowPackage = $packages['Neos.Flow'];
            unset($packages['Neos.Flow']);
            $packages = array_merge(['Neos.Flow' => $flowPackage], $packages);
            unset($flowPackage);
        }

        $settings = [];
        foreach ($packages as $packageKey => $package) {
            if (Arrays::getValueByPath($settings, $packageKey) === null) {
                $settings = Arrays::setValueByPath($settings, $packageKey, []);
            }
            $settings = Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
        }
        $settings = Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

        foreach ($this->orderedListOfContextNames as $contextName) {
            foreach ($packages as $package) {
                $settings = Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource));
            }
            $settings = Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
        }

        if ($configuration !== []) {
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $settings);
        } else {
            $configuration = $settings;
        }

        $configuration['Neos']['Flow']['core']['context'] = (string)$this->context;

        return $configuration;
    }
}

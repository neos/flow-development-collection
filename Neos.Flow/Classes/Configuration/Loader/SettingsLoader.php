<?php
declare(strict_types=1);

namespace Neos\Flow\Configuration\Loader;

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
use Neos\Utility\Arrays;

class SettingsLoader implements LoaderInterface
{
    /**
     * @var YamlSource
     */
    private $yamlSource;

    public function __construct(YamlSource $yamlSource)
    {
        $this->yamlSource = $yamlSource;
    }

    public function load(array $packages, ApplicationContext $context): array
    {
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
            $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->yamlSource->load($package->getConfigurationPath() . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, true));
        }
        $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, true));

        foreach ($context->getHierarchy() as $contextName) {
            foreach ($packages as $package) {
                $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, true));
            }
            $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, true));
        }
        $settings['Neos']['Flow']['core']['context'] = (string)$context;
        return $settings;
    }
}

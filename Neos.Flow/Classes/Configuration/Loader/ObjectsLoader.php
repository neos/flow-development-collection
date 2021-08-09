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

class ObjectsLoader implements LoaderInterface
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
        $configuration = [];
        foreach ($packages as $packageKey => $package) {
            $packageConfiguration = $this->yamlSource->load($package->getConfigurationPath() . ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, true);
            $packageConfiguration = Arrays::arrayMergeRecursiveOverrule($packageConfiguration, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, true));

            foreach ($context->getHierarchy() as $contextName) {
                $packageConfiguration = Arrays::arrayMergeRecursiveOverrule($packageConfiguration, $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, true));
                $packageConfiguration = Arrays::arrayMergeRecursiveOverrule($packageConfiguration, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, true));
            }
            $configuration[$packageKey] = $packageConfiguration;
        }
        return $configuration;
    }
}

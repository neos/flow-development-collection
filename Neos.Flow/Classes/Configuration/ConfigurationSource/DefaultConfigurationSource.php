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

use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Utility\Arrays;

class DefaultConfigurationSource implements ConfigurationSourceInterface
{
    /**
     * @var YamlSource
     */
    private $yamlSource;

    /**
     * @var string
     */
    private $name;

    public function __construct(YamlSource $yamlSource, string $name)
    {
        $this->yamlSource = $yamlSource;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function process(array $packages, ApplicationContext $context): array
    {
        $configuration = [];
        foreach ($packages as $package) {
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($package->getConfigurationPath() . $this->name, true));
        }
        $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $this->name, true));

        foreach ($context->getHierarchy() as $contextName) {
            foreach ($packages as $package) {
                $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . $this->name, true));
            }
            $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $this->name, true));
        }

        return $configuration;
    }
}

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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Utility\Arrays;

class PolicyConfigurationSource implements ConfigurationSourceInterface
{

    /**
     * @var YamlSource
     */
    private $yamlSource;

    /**
     * @var string
     */
    private $temporaryDirectoryPath;

    public function __construct(YamlSource $yamlSource, string $temporaryDirectoryPath)
    {
        $this->yamlSource = $yamlSource;
        $this->temporaryDirectoryPath = $temporaryDirectoryPath;
    }


    public function getName(): string
    {
        return ConfigurationManager::CONFIGURATION_TYPE_POLICY;
    }

    public function process(array $packages, ApplicationContext $context): array
    {
        if ($context->isTesting()) {
            $testingPolicyPathAndFilename = $this->temporaryDirectoryPath . ConfigurationManager::CONFIGURATION_TYPE_POLICY;
            if ($this->yamlSource->has($testingPolicyPathAndFilename)) {
                return $this->yamlSource->load($testingPolicyPathAndFilename);
            }
        }

        $configuration = [];
        foreach ($packages as $package) {
            $packagePolicyConfiguration = $this->yamlSource->load($package->getConfigurationPath() . ConfigurationManager::CONFIGURATION_TYPE_POLICY, true);
            $configuration = $this->mergePolicyConfiguration($configuration, $packagePolicyConfiguration);
        }
        $configuration = $this->mergePolicyConfiguration($configuration, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_POLICY, true));

        foreach ($context->getHierarchy() as $contextName) {
            foreach ($packages as $package) {
                $packagePolicyConfiguration = $this->yamlSource->load($package->getConfigurationPath() . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_POLICY, true);
                $configuration = $this->mergePolicyConfiguration($configuration, $packagePolicyConfiguration);
            }
            $configuration = $this->mergePolicyConfiguration($configuration, $this->yamlSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . ConfigurationManager::CONFIGURATION_TYPE_POLICY, true));
        }

        return $configuration;
    }

    /**
     * Merges two policy configuration arrays.
     *
     * @param array $firstConfigurationArray
     * @param array $secondConfigurationArray
     * @return array
     */
    private function mergePolicyConfiguration(array $firstConfigurationArray, array $secondConfigurationArray): array
    {
        $result = Arrays::arrayMergeRecursiveOverrule($firstConfigurationArray, $secondConfigurationArray);
        if (!isset($result['roles'])) {
            return $result;
        }
        foreach ($result['roles'] as $roleIdentifier => $roleConfiguration) {
            if (!isset($firstConfigurationArray['roles'][$roleIdentifier]['privileges'], $secondConfigurationArray['roles'][$roleIdentifier]['privileges'])) {
                continue;
            }
            $result['roles'][$roleIdentifier]['privileges'] = array_merge($firstConfigurationArray['roles'][$roleIdentifier]['privileges'], $secondConfigurationArray['roles'][$roleIdentifier]['privileges']);
        }
        return $result;
    }
}

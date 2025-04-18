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
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Utility\Arrays;

/**
 * @phpstan-type PolicyDefinition array{roles?: array<string, array{privileges: array<mixed>}>}
 */
class PolicyLoader implements LoaderInterface
{
    /**
     * @var YamlSource
     */
    private $yamlSource;

    /**
     * An absolute file path to store configuration caches in. If null no cache will be active.
     *
     * @var string
     */
    protected $temporaryDirectoryPath;

    public function __construct(YamlSource $yamlSource)
    {
        $this->yamlSource = $yamlSource;
    }

    /**
     * Set an absolute file path to store configuration caches in. If null no cache will be active.
     *
     * @param string $temporaryDirectoryPath
     */
    public function setTemporaryDirectoryPath(string $temporaryDirectoryPath): void
    {
        $this->temporaryDirectoryPath = $temporaryDirectoryPath;
    }

    /**
     * @param array<FlowPackageInterface> $packages
     * @return PolicyDefinition
     */
    public function load(array $packages, ApplicationContext $context): array
    {
        if ($context->isTesting()) {
            $testingPolicyPathAndFilename = $this->temporaryDirectoryPath . 'Policy';
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
     * @param PolicyDefinition $firstConfigurationArray
     * @param PolicyDefinition $secondConfigurationArray
     * @return PolicyDefinition
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

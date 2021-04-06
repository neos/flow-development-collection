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

use Neos\Flow\Configuration\RouteConfigurationProcessor;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Utility\Arrays;

/**
 * Class Loader implementation as fallback to the compoer loader and for test classes.
 *
 */
class PolicyConfigurationType extends AbstractConfigurationType
{
    /**
     * An absolute file path to store configuration caches in used for testing context. If null no cache will be active.
     *
     * @var string
     */
    protected $temporaryDirectoryPath;

    /**
     * Set an absolute file path to store configuration caches in for testing context. If null no cache will be active.
     *
     * @param string $temporaryDirectoryPath
     * @return $this
     */
    public function setTemporaryDirectoryPath(string $temporaryDirectoryPath): self
    {
        $this->temporaryDirectoryPath = $temporaryDirectoryPath;
        return $this;
    }

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

        if ($this->context->isTesting()) {
            $testingPolicyPathAndFilename = $this->temporaryDirectoryPath . 'Policy';
            if ($configurationSource->has($testingPolicyPathAndFilename)) {
                return $configurationSource->load($testingPolicyPathAndFilename, $allowSplitSource);
            }
        }

        foreach ($packages as $package) {
            $packagePolicyConfiguration = $configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource);
            $configuration = $this->mergePolicyConfiguration($configuration, $packagePolicyConfiguration);
        }
        $configuration = $this->mergePolicyConfiguration($configuration, $configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

        foreach ($this->orderedListOfContextNames as $contextName) {
            foreach ($packages as $package) {
                $packagePolicyConfiguration = $configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource);
                $configuration = $this->mergePolicyConfiguration($configuration, $packagePolicyConfiguration);
            }
            $this->configurations[$configurationType] = $this->mergePolicyConfiguration($configuration, $configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
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
    protected function mergePolicyConfiguration(array $firstConfigurationArray, array $secondConfigurationArray): array
    {
        $result = Arrays::arrayMergeRecursiveOverrule($firstConfigurationArray, $secondConfigurationArray);
        if (!isset($result['roles'])) {
            return $result;
        }
        foreach ($result['roles'] as $roleIdentifier => $roleConfiguration) {
            if (!isset($firstConfigurationArray['roles'][$roleIdentifier]['privileges']) || !isset($secondConfigurationArray['roles'][$roleIdentifier]['privileges'])) {
                continue;
            }
            $result['roles'][$roleIdentifier]['privileges'] = array_merge($firstConfigurationArray['roles'][$roleIdentifier]['privileges'], $secondConfigurationArray['roles'][$roleIdentifier]['privileges']);
        }
        return $result;
    }
}

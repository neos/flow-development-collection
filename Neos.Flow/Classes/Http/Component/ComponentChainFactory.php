<?php
namespace Neos\Flow\Http\Component;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;

/**
 * Creates a new ComponentChain according to the specified settings
 *
 * @Flow\Scope("singleton")
 */
class ComponentChainFactory
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param array $chainConfiguration
     * @return ComponentChain
     * @throws Exception
     */
    public function create(array $chainConfiguration)
    {
        if (empty($chainConfiguration)) {
            return null;
        }
        $arraySorter = new PositionalArraySorter($chainConfiguration);
        $sortedChainConfiguration = $arraySorter->toArray();

        $chainComponents = [];
        foreach ($sortedChainConfiguration as $componentName => $configuration) {
            $componentOptions = isset($configuration['componentOptions']) ? $configuration['componentOptions'] : [];
            if (isset($configuration['chain'])) {
                $component = $this->create($configuration['chain']);
            } else {
                if (!isset($configuration['component'])) {
                    throw new Exception(sprintf('Component chain could not be created because no component class name is configured for component "%s"', $componentName), 1401718283);
                }
                $component = $this->objectManager->get($configuration['component'], $componentOptions);
                if (!$component instanceof ComponentInterface) {
                    throw new Exception(sprintf('Component chain could not be created because the class "%s" does not implement the ComponentInterface, in component "%s" does not implement', $configuration['component'], $componentName), 1401718283);
                }
            }
            $chainComponents[] = $component;
        }

        return new ComponentChain(['components' => $chainComponents]);
    }
}

<?php
namespace TYPO3\Flow\Http\Component;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * Creates a new ComponentChain according to the specified settings
 *
 * @Flow\Scope("singleton")
 */
class ComponentChainFactory {

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
	public function create(array $chainConfiguration) {
		if (empty($chainConfiguration)) {
			return NULL;
		}
		$arraySorter = new PositionalArraySorter($chainConfiguration);
		$sortedChainConfiguration = $arraySorter->toArray();

		$chainComponents = array();
		foreach ($sortedChainConfiguration as $componentName => $configuration) {
			$componentOptions = isset($configuration['componentOptions']) ? $configuration['componentOptions'] : array();
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

		return new ComponentChain(array('components' => $chainComponents));
	}

}
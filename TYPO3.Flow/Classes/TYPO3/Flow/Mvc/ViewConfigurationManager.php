<?php
namespace TYPO3\Flow\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Context;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\RequestMatcher;
use TYPO3\Flow\Utility\Arrays;

/**
 * A View Configuration Manager
 *
 * This classes compiles all configurations matching the provided
 * request out of the Views.yaml into one view configuration used
 * by the ActionController to setup up the view.
 *
 * @Flow\Scope("singleton")
 */
class ViewConfigurationManager {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Eel\CompilingEvaluator
	 */
	protected $eelEvaluator;

	/**
	 * This method walks through the view configuration and applies
	 * matching configurations in the order of their specifity score.
	 * Possible options are currently the viewObjectName to specify
	 * a different class that will be used to create the view and
	 * an array of options that will be set on the view object.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request
	 * @return array
	 */
	public function getViewConfiguration(ActionRequest $request) {
		$cacheIdentifiersParts = array();
		$cacheIdentifier = $this->createCacheIdentifier($request);

		$viewConfiguration = $this->cache->get($cacheIdentifier);
		if ($viewConfiguration === FALSE) {
			$configurations = $this->configurationManager->getConfiguration('Views');

			$requestMatcher = new RequestMatcher($request);
			$context = new Context($requestMatcher);
			$matchingConfigurations = array();
			foreach ($configurations as $order => $configuration) {
				$requestMatcher->resetWeight();
				if (!isset($configuration['requestFilter'])) {
					$matchingConfigurations[$order]['configuration'] = $configuration;
					$matchingConfigurations[$order]['weight'] = $order;
				} else {
					$result = $this->eelEvaluator->evaluate($configuration['requestFilter'], $context);
					if ($result === FALSE) {
						continue;
					}
					$matchingConfigurations[$order]['configuration'] = $configuration;
					$matchingConfigurations[$order]['weight'] = $requestMatcher->getWeight() + $order;
				}
			}

			usort($matchingConfigurations, function($configuration1, $configuration2) {
				return $configuration1['weight'] > $configuration2['weight'];
			});

			$viewConfiguration = array();
			foreach ($matchingConfigurations as $key => $matchingConfiguration) {
				$viewConfiguration = Arrays::arrayMergeRecursiveOverrule($viewConfiguration, $matchingConfiguration['configuration']);
			}
			$this->cache->set($cacheIdentifier, $viewConfiguration);
		}

		return $viewConfiguration;
	}

	/**
	 * Create a complete cache identifier for the given
	 * request that conforms to cache identifier syntax
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request
	 * @return string
	 */
	protected function createCacheIdentifier($request) {
		do {
			$cacheIdentifiersParts[] = $request->getControllerPackageKey();
			$cacheIdentifiersParts[] = $request->getControllerSubpackageKey();
			$cacheIdentifiersParts[] = $request->getControllerName();
			$cacheIdentifiersParts[] = $request->getControllerActionName();
			$cacheIdentifiersParts[] = $request->getFormat();
			$request = $request->getParentRequest();
		} while ($request instanceof ActionRequest);
		return md5(implode('-', $cacheIdentifiersParts));
	}
}

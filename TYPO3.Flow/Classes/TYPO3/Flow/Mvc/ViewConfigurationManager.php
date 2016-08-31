<?php
namespace TYPO3\Flow\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\Context;
use TYPO3\Flow\Annotations as Flow;
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
class ViewConfigurationManager
{
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
    public function getViewConfiguration(ActionRequest $request)
    {
        $cacheIdentifier = $this->createCacheIdentifier($request);

        $viewConfiguration = $this->cache->get($cacheIdentifier);
        if ($viewConfiguration === false) {
            $configurations = $this->configurationManager->getConfiguration('Views');

            $requestMatcher = new RequestMatcher($request);
            $context = new Context($requestMatcher);
            $matchingConfigurations = array();
            foreach ($configurations as $order => $configuration) {
                $requestMatcher->resetWeight();
                if (!isset($configuration['requestFilter'])) {
                    $matchingConfigurations[$order]['configuration'] = $configuration;
                    $matchingConfigurations[$order]['weight'] = $order;
                    continue;
                }

                $result = $this->eelEvaluator->evaluate($configuration['requestFilter'], $context);
                if ($result === false) {
                    continue;
                }
                $matchingConfigurations[$order]['configuration'] = $configuration;
                $matchingConfigurations[$order]['weight'] = $requestMatcher->getWeight() + $order;
            }

            usort($matchingConfigurations, function ($configuration1, $configuration2) {
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
    protected function createCacheIdentifier($request)
    {
        $cacheIdentifiersParts = array();
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

<?php
namespace Neos\Flow\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Context;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Utility\Arrays;

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
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * This method walks through the view configuration and applies
     * matching configurations in the order of their specifity score.
     * Possible options are currently the viewObjectName to specify
     * a different class that will be used to create the view and
     * an array of options that will be set on the view object.
     *
     * @param ActionRequest $request
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

            $viewConfiguration = [];
            $highestWeight = -1;
            foreach ($configurations as $order => $configuration) {
                $requestMatcher->resetWeight();
                if (!isset($configuration['requestFilter'])) {
                    $weight = $order;
                } else {
                    $result = $this->eelEvaluator->evaluate($configuration['requestFilter'], $context);
                    if ($result === false) {
                        continue;
                    }
                    $weight = $requestMatcher->getWeight() + $order;
                }
                if ($weight > $highestWeight) {
                    $viewConfiguration = $configuration;
                    $highestWeight = $weight;
                }
            }
            $this->cache->set($cacheIdentifier, $viewConfiguration);
        }

        return $viewConfiguration;
    }

    /**
     * Create a complete cache identifier for the given
     * request that conforms to cache identifier syntax
     *
     * @param RequestInterface $request
     * @return string
     */
    protected function createCacheIdentifier($request)
    {
        $cacheIdentifiersParts = [];
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

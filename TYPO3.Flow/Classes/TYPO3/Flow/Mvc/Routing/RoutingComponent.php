<?php
namespace TYPO3\Flow\Mvc\Routing;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Component\Exception as ComponentException;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Component\ComponentInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * A routing HTTP component
 */
class RoutingComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
     * @var Router
     */
    protected $router;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * Resolve a route for the request
     *
     * Stores the resolved route values in the ComponentContext to pass them
     * to other components. They can be accessed via ComponentContext::getParameter('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults');
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        if ($componentContext->getParameter('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'skipRouterInitialization') !== true) {
            $routesConfiguration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
            $this->router->setRoutesConfiguration($routesConfiguration);
        }

        $matchResults = $this->router->route($componentContext->getHttpRequest());
        $componentContext->setParameter('TYPO3\Flow\Mvc\Routing\RoutingComponent', 'matchResults', $matchResults);
    }
}

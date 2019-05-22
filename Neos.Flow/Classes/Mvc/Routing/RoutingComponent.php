<?php
namespace Neos\Flow\Mvc\Routing;

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
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Package\PackageManager;

/**
 * A routing HTTP component
 */
class RoutingComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
     * @var RouterInterface
     */
    protected $router;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Resolve a route for the request
     *
     * Stores the resolved route values in the ComponentContext to pass them
     * to other components. They can be accessed via ComponentContext::getParameter(outingComponent::class, 'matchResults');
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $parameters = $componentContext->getParameter(RoutingComponent::class, 'parameters');
        if ($parameters === null) {
            $parameters = RouteParameters::createEmpty();
        }
        $routeContext = new RouteContext($componentContext->getHttpRequest(), $parameters);

        try {
            $matchResults = $this->router->route($routeContext);
        } catch (NoMatchingRouteException $exception) {
            $matchResults = null;
        }

        if (isset($matchResults['@package'])) {
            $matchResults['@package'] = $this->packageManager->getCaseSensitivePackageKey($matchResults['@package']);
        }

        $componentContext->setParameter(RoutingComponent::class, 'matchResults', $matchResults);
        $httpRequest = $componentContext->getHttpRequest()->withAttribute(ServerRequestAttributes::ROUTING_RESULTS, $matchResults);
        $componentContext->replaceHttpRequest($httpRequest);
    }
}

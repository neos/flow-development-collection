<?php
namespace Neos\Flow\Security\Authorization;

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Security\RequestPatternResolver;

/**
 * Default Firewall which analyzes the request with a RequestFilter chain.
 *
 * @Flow\Scope("singleton")
 */
class FilterFirewall implements FirewallInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * @var RequestPatternResolver
     */
    protected $requestPatternResolver = null;

    /**
     * @var InterceptorResolver
     */
    protected $interceptorResolver = null;

    /**
     * @var RequestFilter[]
     */
    protected $filters = [];

    /**
     * If set to true the firewall will reject any request except the ones explicitly
     * allowed by a \Neos\Flow\Security\Authorization\AccessGrantInterceptor
     * @var boolean
     */
    protected $rejectAll = false;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager The object manager
     * @param RequestPatternResolver $requestPatternResolver The request pattern resolver
     * @param InterceptorResolver $interceptorResolver The interceptor resolver
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        RequestPatternResolver $requestPatternResolver,
        InterceptorResolver $interceptorResolver
    ) {
        $this->objectManager = $objectManager;
        $this->requestPatternResolver = $requestPatternResolver;
        $this->interceptorResolver = $interceptorResolver;
    }

    /**
     * Injects the configuration settings
     *
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->rejectAll = $settings['security']['firewall']['rejectAll'];
        $this->filters = array_map([$this, 'createFilterFromConfiguration'], array_values($settings['security']['firewall']['filters']));
    }

    /**
     * Analyzes a request against the configured firewall rules and blocks
     * any illegal request.
     *
     * @param ActionRequest $request The request to be analyzed
     * @return void
     * @throws AccessDeniedException
     */
    public function blockIllegalRequests(ActionRequest $request)
    {
        $filterMatched = array_reduce($this->filters, function (bool $filterMatched, RequestFilter $filter) use ($request) {
            return ($filter->filterRequest($request) ? true : $filterMatched);
        }, false);

        if ($this->rejectAll && !$filterMatched) {
            throw new AccessDeniedException('The request was blocked, because no request filter explicitly allowed it.', 1216923741);
        }
    }

    /**
     * @param array $filterConfiguration
     * @return RequestFilter
     * @throws \Neos\Flow\Security\Exception\NoInterceptorFoundException
     * @throws \Neos\Flow\Security\Exception\NoRequestPatternFoundException
     */
    protected function createFilterFromConfiguration(array $filterConfiguration): RequestFilter
    {
        $patternType = isset($filterConfiguration['pattern']) ? $filterConfiguration['pattern'] : $filterConfiguration['patternType'];
        $patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternType);

        $patternOptions = isset($filterConfiguration['patternOptions']) ? $filterConfiguration['patternOptions'] : [];
        /** @var $requestPattern RequestPatternInterface */
        $requestPattern = $this->objectManager->get($patternClassName, $patternOptions);

        /** @var InterceptorInterface $interceptor */
        $interceptor = $this->objectManager->get($this->interceptorResolver->resolveInterceptorClass($filterConfiguration['interceptor']));
        return new RequestFilter($requestPattern, $interceptor);
    }
}

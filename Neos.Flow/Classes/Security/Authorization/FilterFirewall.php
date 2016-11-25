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
     * @var array<RequestFilter>
     */
    protected $filters = [];

    /**
     * If set to TRUE the firewall will reject any request except the ones explicitly
     * whitelisted by a \Neos\Flow\Security\Authorization\AccessGrantInterceptor
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
    public function __construct(ObjectManagerInterface $objectManager,
                                RequestPatternResolver $requestPatternResolver,
                                InterceptorResolver $interceptorResolver)
    {
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
        $this->buildFiltersFromSettings($settings['security']['firewall']['filters']);
    }

    /**
     * Analyzes a request against the configured firewall rules and blocks
     * any illegal request.
     *
     * @param ActionRequest $request The request to be analyzed
     * @return void
     * @throws AccessDeniedException if the
     */
    public function blockIllegalRequests(ActionRequest $request)
    {
        $filterMatched = false;
        /** @var $filter RequestFilter */
        foreach ($this->filters as $filter) {
            if ($filter->filterRequest($request)) {
                $filterMatched = true;
            }
        }
        if ($this->rejectAll && !$filterMatched) {
            throw new AccessDeniedException('The request was blocked, because no request filter explicitly allowed it.', 1216923741);
        }
    }

    /**
     * Sets the internal filters based on the given configuration.
     *
     * @param array $filterSettings The filter settings
     * @return void
     */
    protected function buildFiltersFromSettings(array $filterSettings)
    {
        foreach ($filterSettings as $singleFilterSettings) {
            $patternType = isset($singleFilterSettings['pattern']) ? $singleFilterSettings['pattern'] : $singleFilterSettings['patternType'];
            $patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternType);

            $patternOptions = isset($singleFilterSettings['patternOptions']) ? $singleFilterSettings['patternOptions'] : [];
            /** @var $requestPattern RequestPatternInterface */
            $requestPattern = $this->objectManager->get($patternClassName, $patternOptions);

            // The following check needed for backwards compatibility:
            // Previously each pattern had only one option that was set via the setPattern() method. Now options are passed to the constructor.
            if (isset($singleFilterSettings['patternValue']) && is_callable([$requestPattern, 'setPattern'])) {
                $requestPattern->setPattern($singleFilterSettings['patternValue']);
            }
            $interceptor = $this->objectManager->get($this->interceptorResolver->resolveInterceptorClass($singleFilterSettings['interceptor']));

            $this->filters[] = $this->objectManager->get(RequestFilter::class, $requestPattern, $interceptor);
        }
    }
}

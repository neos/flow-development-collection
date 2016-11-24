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

use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * A RequestFilter is configured to match specific \Neos\Flow\Mvc\RequestInterfaces and call
 * a InterceptorInterface if needed.
 *
 */
class RequestFilter
{
    /**
     * @var RequestPatternInterface
     */
    protected $pattern = null;

    /**
     * @var InterceptorInterface
     */
    protected $securityInterceptor = null;

    /**
     * Constructor.
     *
     * @param RequestPatternInterface $pattern The pattern this filter matches
     * @param InterceptorInterface $securityInterceptor The interceptor called on pattern match
     */
    public function __construct(RequestPatternInterface $pattern, InterceptorInterface $securityInterceptor)
    {
        $this->pattern = $pattern;
        $this->securityInterceptor = $securityInterceptor;
    }

    /**
     * Returns the set request pattern
     *
     * @return RequestPatternInterface The set request pattern
     */
    public function getRequestPattern()
    {
        return $this->pattern;
    }

    /**
     * Returns the set security interceptor
     *
     * @return InterceptorInterface The set security interceptor
     */
    public function getSecurityInterceptor()
    {
        return $this->securityInterceptor;
    }

    /**
     * Tries to match the given request against this filter and calls the set security interceptor on success.
     *
     * @param RequestInterface $request The request to be matched
     * @return boolean Returns TRUE if the filter matched, FALSE otherwise
     */
    public function filterRequest(RequestInterface $request)
    {
        if ($this->pattern->matchRequest($request)) {
            $this->securityInterceptor->invoke();
            return true;
        }
        return false;
    }
}

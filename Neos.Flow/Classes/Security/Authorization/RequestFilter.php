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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\NoTokensAuthenticatedException;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * A RequestFilter is configured to match specific ActionRequests and call
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
    public function getRequestPattern(): RequestPatternInterface
    {
        return $this->pattern;
    }

    /**
     * Returns the set security interceptor
     *
     * @return InterceptorInterface The set security interceptor
     */
    public function getSecurityInterceptor(): InterceptorInterface
    {
        return $this->securityInterceptor;
    }

    /**
     * Tries to match the given request against this filter and calls the set security interceptor on success.
     *
     * @param ActionRequest $request The request to be matched
     * @return boolean Returns true if the filter matched, false otherwise
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException
     * @throws NoTokensAuthenticatedException
     */
    public function filterRequest(ActionRequest $request): bool
    {
        if ($this->pattern->matchRequest($request)) {
            return $this->securityInterceptor->invoke() !== false;
        }
        return false;
    }
}

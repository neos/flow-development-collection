<?php
namespace Neos\Flow\Security\Exception;

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

/**
 * An "AccessDenied" Exception
 *
 * @api
 */
class AuthenticationRequiredException extends \Neos\Flow\Security\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 401;

    /**
     * @var ActionRequest
     */
    protected $interceptedRequest;

    /**
     * Attach the given action request as intercepted request and return self.
     *
     * @param ActionRequest $actionRequest
     * @return AuthenticationRequiredException
     */
    public function attachInterceptedRequest(ActionRequest $actionRequest): self
    {
        $this->interceptedRequest = $actionRequest;
        return $this;
    }

    /**
     * @return bool True if this instance has an intercepted ActionRequest attached
     */
    public function hasInterceptedRequest(): bool
    {
        return $this->interceptedRequest !== null;
    }

    /**
     * @return ActionRequest|null The attached intercepted ActionRequest or null
     */
    public function getInterceptedRequest(): ?ActionRequest
    {
        return $this->interceptedRequest;
    }
}

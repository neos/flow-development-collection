<?php
namespace Neos\Flow\Security\Channel;

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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\InterceptorInterface;
use Neos\Flow\Security\Context;

/**
 * This security interceptor switches the current channel between HTTP and HTTPS protocol.
 *
 * @Flow\Scope("singleton")
 */
class HttpsInterceptor implements InterceptorInterface
{
    /**
     * @var boolean
     * @todo this has to be set by configuration
     */
    protected $useSSL = false;

    /**
     * Constructor.
     *
     * @param Context $securityContext The current security context
     * @param AuthenticationManagerInterface $authenticationManager The authentication Manager
     * @param SystemLoggerInterface $logger A logger to log security relevant actions
     */
    public function __construct(
        Context $securityContext,
        AuthenticationManagerInterface $authenticationManager,
        SystemLoggerInterface $logger
    ) {
    }

    /**
     * Redirects the current request to HTTP or HTTPS depending on $this->useSSL;
     *
     * @return boolean TRUE if the security checks was passed
     */
    public function invoke()
    {
    }
}

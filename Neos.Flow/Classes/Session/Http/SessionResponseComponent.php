<?php
namespace Neos\Flow\Session\Http;

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
use Neos\Flow\Session\SessionManagerInterface;

/**
 * A component to set a cookie header for the standard Flow session.
 */
class SessionResponseComponent implements ComponentInterface
{
    /**
     * @Flow\Inject
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @param ComponentContext $componentContext
     */
    public function handle(ComponentContext $componentContext)
    {
        $response = $componentContext->getHttpResponse();
        $currentSession = $this->sessionManager->getCurrentSession();
        if (!$currentSession->isStarted()) {
            return;
        }

        $response = $response->withAddedHeader('Set-Cookie', (string)$currentSession->getSessionCookie());
        $componentContext->replaceHttpResponse($response);
    }
}

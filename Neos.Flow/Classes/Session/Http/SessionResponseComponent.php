<?php
namespace Neos\Flow\Session\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Session\SessionManagerInterface;

/**
 *
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

        $response = $response->withHeader('Set-Cookie', (string)$currentSession->getSessionCookie());
        $componentContext->replaceHttpResponse($response);
    }
}

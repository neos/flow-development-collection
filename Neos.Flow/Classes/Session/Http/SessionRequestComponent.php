<?php
namespace Neos\Flow\Session\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\Flow\Utility\Algorithms;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class SessionRequestComponent implements ComponentInterface
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="session")
     * @var array
     */
    protected $sessionSettings;

    /**
     * @Flow\Inject(lazy=false)
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @param ComponentContext $componentContext
     */
    public function handle(ComponentContext $componentContext)
    {
        if (!$this->sessionManager instanceof SessionManager) {
            return;
        }

        $sessionCookieName = $this->sessionSettings['name'];
        /** @var ServerRequestInterface $request */
        $request = $componentContext->getHttpRequest();
        $cookies = $request->getCookieParams();

        if (!isset($cookies[$sessionCookieName]) ) {
            $sessionCookie = $this->prepareCookie($sessionCookieName, Algorithms::generateRandomString(32));
            $this->sessionManager->createCurrentSessionFromCookie($sessionCookie);
            return;
        }

        $sessionIdentifier = $cookies[$sessionCookieName];
        $sessionCookie = $this->prepareCookie($sessionCookieName, $sessionIdentifier);
        $this->sessionManager->initializeCurrentSessionFromCookie($sessionCookie);
        $this->sessionManager->getCurrentSession()->resume();
    }

    /**
     * @param string $name
     * @param string $value
     * @return Cookie
     */
    protected function prepareCookie(string $name, string $value)
    {
        return new Cookie(
            $name,
            $value,
            0,
            $this->sessionSettings['cookie']['lifetime'],
            $this->sessionSettings['cookie']['domain'],
            $this->sessionSettings['cookie']['path'],
            $this->sessionSettings['cookie']['secure'],
            $this->sessionSettings['cookie']['httponly']
        );
    }
}

<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

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
use Neos\Flow\Http\Cookie;
use Neos\Flow\Session\CookieEnabledInterface;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\Flow\Utility\Algorithms;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A middleware that handles the session in a HTTP request
 */
class SessionMiddleware implements MiddlewareInterface
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if (!$this->sessionManager instanceof SessionManager) {
            return $next->handle($request);
        }

        $sessionCookieName = $this->sessionSettings['name'];
        /** @var ServerRequestInterface $request */
        $cookies = $request->getCookieParams();

        if (!isset($cookies[$sessionCookieName])) {
            $sessionCookie = $this->prepareCookie($sessionCookieName, Algorithms::generateRandomString(32));
            $this->sessionManager->createCurrentSessionFromCookie($sessionCookie);
            return $this->handleSetCookie($next->handle($request));
        }

        $sessionIdentifier = $cookies[$sessionCookieName];
        $sessionCookie = $this->prepareCookie((string) $sessionCookieName, (string) $sessionIdentifier);
        $this->sessionManager->initializeCurrentSessionFromCookie($sessionCookie);
        $this->sessionManager->getCurrentSession()->resume();

        return $this->handleSetCookie($next->handle($request));
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function handleSetCookie(ResponseInterface $response): ResponseInterface
    {
        $currentSession = $this->sessionManager->getCurrentSession();
        if (!$currentSession->isStarted() || !($currentSession instanceof CookieEnabledInterface)) {
            return $response;
        }

        return $response->withAddedHeader('Set-Cookie', (string)$currentSession->getSessionCookie());
    }

    /**
     * Prepares a cookie object for the session.
     *
     * @param string $name
     * @param string $value
     * @return Cookie
     */
    protected function prepareCookie(string $name, string $value)
    {
        return new Cookie(
            $name,
            // @see https://github.com/neos/flow-development-collection/issues/2133
            trim(urldecode($value), '"'),
            0,
            $this->sessionSettings['cookie']['lifetime'],
            $this->sessionSettings['cookie']['domain'],
            $this->sessionSettings['cookie']['path'],
            $this->sessionSettings['cookie']['secure'],
            $this->sessionSettings['cookie']['httponly'],
            $this->sessionSettings['cookie']['samesite']
        );
    }
}

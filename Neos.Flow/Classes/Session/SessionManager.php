<?php
namespace Neos\Flow\Session;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Utility\Algorithms;
use Psr\Log\LoggerInterface;

/**
 * Session Manager
 *
 * @Flow\Scope("singleton")
 */
class SessionManager implements SessionManagerInterface
{
    /**
     * @var SessionInterface
     */
    protected $currentSession;

    /**
     * @var array
     */
    protected $remoteSessions;

    /**
     * Meta data cache used by sessions
     *
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $metaDataCache;

    /**
     * Storage cache for by sessions
     *
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $storageCache;

    /**
     * @var float
     * @Flow\InjectConfiguration(path="session.garbageCollection.probability")
     */
    protected $garbageCollectionProbability;

    /**
     * @Flow\InjectConfiguration(path="session.garbageCollection.maximumPerRun")
     * @var integer
     */
    protected $garbageCollectionMaximumPerRun;

    /**
     * @Flow\InjectConfiguration(path="session.inactivityTimeout")
     * @var integer
     */
    protected $inactivityTimeout;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Returns the currently active session which stores session data for the
     * current HTTP request on this local system.
     *
     * @return SessionInterface
     * @api
     */
    public function getCurrentSession()
    {
        if ($this->currentSession === null) {
            $this->currentSession = new Session();
        }
        return $this->currentSession;
    }

    /**
     * @param Cookie $cookie
     * @return bool
     */
    public function initializeCurrentSessionFromCookie(Cookie $cookie)
    {
        if ($this->currentSession !== null && $this->currentSession->isStarted()) {
            return false;
        }

        $sessionIdentifier = $cookie->getValue();
        $sessionInfo = $this->metaDataCache->get($sessionIdentifier);

        if (!$sessionInfo) {
            return false;
        }

        $this->currentSession = Session::createFromCookieAndSessionInformation($cookie, $sessionInfo['storageIdentifier'], $sessionInfo['lastActivityTimestamp'], $sessionInfo['tags']);
        return true;
    }

    /**
     * @param Cookie $cookie
     * @return bool
     */
    public function createCurrentSessionFromCookie(Cookie $cookie)
    {
        if ($this->currentSession !== null && $this->currentSession->isStarted()) {
            return false;
        }

        $this->currentSession = Session::createFromCookieAndSessionInformation($cookie, Algorithms::generateUUID(), time());

        return true;
    }

    /**
     * Returns the specified session. If no session with the given identifier exists,
     * NULL is returned.
     *
     * @param string $sessionIdentifier The session identifier
     * @return SessionInterface|null
     * @api
     */
    public function getSession($sessionIdentifier)
    {
        if ($this->currentSession !== null && $this->currentSession->isStarted() && $this->currentSession->getId() === $sessionIdentifier) {
            return $this->currentSession;
        }
        if (isset($this->remoteSessions[$sessionIdentifier])) {
            return $this->remoteSessions[$sessionIdentifier];
        }
        if ($this->metaDataCache->has($sessionIdentifier)) {
            $sessionInfo = $this->metaDataCache->get($sessionIdentifier);
            $this->remoteSessions[$sessionIdentifier] = new Session($sessionIdentifier, $sessionInfo['storageIdentifier'], $sessionInfo['lastActivityTimestamp'], $sessionInfo['tags']);
            return $this->remoteSessions[$sessionIdentifier];
        }
        return null;
    }

    /**
     * Returns all active sessions, even remote ones.
     *
     * @return array<SessionInterface>
     * @api
     */
    public function getActiveSessions()
    {
        $activeSessions = [];
        foreach ($this->metaDataCache->getByTag('session') as $sessionIdentifier => $sessionInfo) {
            $session = new Session($sessionIdentifier, $sessionInfo['storageIdentifier'], $sessionInfo['lastActivityTimestamp'], $sessionInfo['tags']);
            $activeSessions[] = $session;
        }
        return $activeSessions;
    }

    /**
     * Returns all sessions which are tagged by the specified tag.
     *
     * @param string $tag A valid Cache Frontend tag
     * @return array A collection of Session objects or an empty array if tag did not match
     * @api
     */
    public function getSessionsByTag($tag)
    {
        $taggedSessions = [];
        foreach ($this->metaDataCache->getByTag(Session::TAG_PREFIX . $tag) as $sessionIdentifier => $sessionInfo) {
            $session = new Session($sessionIdentifier, $sessionInfo['storageIdentifier'], $sessionInfo['lastActivityTimestamp'], $sessionInfo['tags']);
            $taggedSessions[] = $session;
        }
        return $taggedSessions;
    }

    /**
     * Destroys all sessions which are tagged with the specified tag.
     *
     * @param string $tag A valid Cache Frontend tag
     * @param string $reason A reason to mention in log output for why the sessions have been destroyed. For example: "The corresponding account was deleted"
     * @return integer Number of sessions which have been destroyed
     */
    public function destroySessionsByTag($tag, $reason = '')
    {
        $sessions = $this->getSessionsByTag($tag);
        foreach ($sessions as $session) {
            /** @var SessionInterface $session */
            $session->destroy($reason);
        }
        return count($sessions);
    }

    /**
     * Iterates over all existing sessions and removes their data if the inactivity
     * timeout was reached.
     *
     * @return integer|null The number of outdated entries removed, null in case the garbage-collection was already running
     * @api
     */
    public function collectGarbage(): ?int
    {
        if ($this->inactivityTimeout === 0) {
            return 0;
        }
        if ($this->metaDataCache->has('_garbage-collection-running')) {
            return null;
        }

        $now = time();
        $sessionRemovalCount = 0;
        $this->metaDataCache->set('_garbage-collection-running', true, [], 120);

        foreach ($this->metaDataCache->getIterator() as $sessionIdentifier => $sessionInfo) {
            if ($sessionIdentifier === '_garbage-collection-running') {
                continue;
            }
            if (!is_array($sessionInfo)) {
                $sessionInfo = [
                    'sessionMetaData' => $sessionInfo,
                    'lastActivityTimestamp' => 0,
                    'storageIdentifier' => null
                ];
                $this->logger->warning('SESSION INFO INVALID: ' . $sessionIdentifier, $sessionInfo + LogEnvironment::fromMethodName(__METHOD__));
            }
            $lastActivitySecondsAgo = $now - $sessionInfo['lastActivityTimestamp'];
            if ($lastActivitySecondsAgo > $this->inactivityTimeout) {
                if ($sessionInfo['storageIdentifier'] !== null) {
                    $this->storageCache->flushByTag($sessionInfo['storageIdentifier']);
                    $sessionRemovalCount++;
                }
                $this->metaDataCache->remove($sessionIdentifier);
            }
            if ($sessionRemovalCount >= $this->garbageCollectionMaximumPerRun) {
                break;
            }
        }

        $this->metaDataCache->remove('_garbage-collection-running');
        return $sessionRemovalCount;
    }

    /**
     * Shuts down this session
     *
     * This method must not be called manually – it is invoked by Flow's object
     * management.
     *
     * @return void
     * @throws Exception\DataNotSerializableException
     * @throws Exception\SessionNotStartedException
     * @throws NotSupportedByBackendException
     * @throws \Neos\Cache\Exception
     */
    public function shutdownObject()
    {
        $decimals = strlen(strrchr((string)$this->garbageCollectionProbability, '.')) - 1;
        $factor = ($decimals > -1) ? $decimals * 10 : 1;
        if (rand(1, 100 * $factor) <= ($this->garbageCollectionProbability * $factor)) {
            $this->collectGarbage();
        }
    }
}

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
use Neos\Flow\Http\Cookie;
use Neos\Flow\Session\Data\SessionDataStore;
use Neos\Flow\Session\Data\SessionMetaDataStore;
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
     * Meta data storage for sessions
     *
     * @Flow\Inject
     * @var SessionMetaDataStore
     */
    protected $sessionMetaDataStore;

    /**
     * Storage for sessions data
     *
     * @Flow\Inject
     * @var SessionDataStore
     */
    protected $sessionDataStore;

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
            $this->currentSession = Session::create();
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
        $sessionMetaData = $this->sessionMetaDataStore->retrieve($sessionIdentifier);

        if (!$sessionMetaData) {
            return false;
        }

        $this->currentSession = Session::createFromCookieAndSessionInformation($cookie, $sessionMetaData->storageIdentifier, $sessionMetaData->lastActivityTimestamp, $sessionMetaData->tags);
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
        if ($this->sessionMetaDataStore->has($sessionIdentifier)) {
            $sessionMetaData = $this->sessionMetaDataStore->retrieve($sessionIdentifier);
            $this->remoteSessions[$sessionIdentifier] = Session::createRemoteFromSessionMetaData($sessionMetaData);
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
        foreach ($this->sessionMetaDataStore->retrieveAll() as $sessionIdentifier => $sessionMetaData) {
            $session = Session::createRemoteFromSessionMetaData($sessionMetaData);
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
        foreach ($this->sessionMetaDataStore->retrieveByTag($tag) as $sessionIdentifier => $sessionMetaData) {
            $session = Session::createRemoteFromSessionMetaData($sessionMetaData);
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
        if ($this->sessionMetaDataStore->isGarbageCollectionRunning()) {
            return null;
        }

        $now = time();
        $sessionRemovalCount = 0;
        $this->sessionMetaDataStore->startGarbageCollection();

        foreach ($this->sessionMetaDataStore->retrieveAll() as $sessionIdentifier => $sessionMetadata) {
            $lastActivitySecondsAgo = $now - $sessionMetadata->lastActivityTimestamp;
            if ($lastActivitySecondsAgo > $this->inactivityTimeout) {
                if ($sessionMetadata->lastActivityTimestamp !== null) {
                    $this->sessionDataStore->remove($sessionMetadata);
                    $sessionRemovalCount++;
                }
                $this->sessionMetaDataStore->remove($sessionMetadata);
            }
            if ($sessionRemovalCount >= $this->garbageCollectionMaximumPerRun) {
                break;
            }
        }

        $this->sessionMetaDataStore->endGarbageCollection();
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

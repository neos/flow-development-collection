<?php
declare(strict_types=1);
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

use Neos\Cache\Exception as CacheException;
use Neos\Cache\Exception\NotSupportedByBackendException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Session\Data\SessionIdentifier;
use Neos\Flow\Session\Data\SessionKeyValueStore;
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Flow\Session\Exception\InvalidDataInSessionDataStoreException;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Utility\Algorithms;

#[Flow\Scope("singleton")]
class SessionManager implements SessionManagerInterface
{
    protected ?SessionInterface $currentSession = null;
    protected array $remoteSessions = [];

    public function __construct(
        readonly protected SessionMetaDataStore $sessionMetaDataStore,
        readonly protected SessionKeyValueStore $sessionKeyValueStore,
        #[Flow\InjectConfiguration(path: "session.garbageCollection.probability")]
        readonly protected float $garbageCollectionProbability,
        #[Flow\InjectConfiguration(path: "session.garbageCollection.maximumPerRun")]
        readonly protected int $garbageCollectionMaximumPerRun,
        #[Flow\InjectConfiguration(path: "session.inactivityTimeout")]
        readonly protected int $inactivityTimeout,
    ) {
    }

    /**
     * Returns the currently active session which stores session data for the
     * current HTTP request on this local system.
     *
     * @api
     */
    public function getCurrentSession(): SessionInterface
    {
        if ($this->currentSession === null) {
            $this->currentSession = Session::create();
        }
        return $this->currentSession;
    }

    /**
     * @throws InvalidDataInSessionDataStoreException
     */
    public function initializeCurrentSessionFromCookie(Cookie $cookie): bool
    {
        if ($this->currentSession !== null && $this->currentSession->isStarted()) {
            return false;
        }

        $sessionIdentifierString = $cookie->getValue();
        $sessionIdentifier = SessionIdentifier::createFromString($sessionIdentifierString);
        $sessionMetaData = $this->sessionMetaDataStore->retrieve($sessionIdentifier);

        if (!$sessionMetaData) {
            return false;
        }

        $this->currentSession = Session::createFromCookieAndSessionInformation($cookie, $sessionMetaData->storageIdentifier->value, $sessionMetaData->lastActivityTimestamp, $sessionMetaData->tags);
        return true;
    }

    /**
     * @throws \Exception
     */
    public function createCurrentSessionFromCookie(Cookie $cookie): bool
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
     * @throws InvalidDataInSessionDataStoreException
     * @throws SessionNotStartedException
     * @api
     */
    public function getSession(string $sessionIdentifier): ?SessionInterface
    {
        if ($this->currentSession !== null && $this->currentSession->isStarted() && $this->currentSession->getId() === $sessionIdentifier) {
            return $this->currentSession;
        }
        if (isset($this->remoteSessions[$sessionIdentifier])) {
            return $this->remoteSessions[$sessionIdentifier];
        }
        $sessionIdentifierObject = SessionIdentifier::createFromString($sessionIdentifier);
        if (!$this->sessionMetaDataStore->has($sessionIdentifierObject)) {
            return null;
        }
        $sessionMetaData = $this->sessionMetaDataStore->retrieve($sessionIdentifierObject);
        if ($sessionMetaData === null) {
            return null;
        }
        $this->remoteSessions[$sessionIdentifierObject->value] = Session::createRemoteFromSessionMetaData($sessionMetaData);
        return $this->remoteSessions[$sessionIdentifierObject->value];
    }

    /**
     * Returns all active sessions, even remote ones.
     *
     * @return array<SessionInterface>
     * @throws NotSupportedByBackendException
     * @api
     */
    public function getActiveSessions(): array
    {
        $activeSessions = [];
        foreach ($this->sessionMetaDataStore->retrieveAll() as $sessionMetaData) {
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
     * @throws NotSupportedByBackendException
     * @api
     */
    public function getSessionsByTag(string $tag): array
    {
        $taggedSessions = [];
        foreach ($this->sessionMetaDataStore->retrieveByTag($tag) as $sessionMetaData) {
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
     * @return int Number of sessions which have been destroyed
     * @throws SessionNotStartedException
     * @throws NotSupportedByBackendException
     */
    public function destroySessionsByTag(string $tag, string $reason = ''): int
    {
        $sessions = $this->getSessionsByTag($tag);
        foreach ($sessions as $session) {
            $session->destroy($reason);
        }
        return count($sessions);
    }

    /**
     * Iterates over all existing sessions and removes their data if the inactivity
     * timeout was reached.
     *
     * @return int|null The number of outdated entries removed, null in case the garbage-collection was already running
     * @throws CacheException
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

        foreach ($this->sessionMetaDataStore->retrieveAll() as $sessionMetadata) {
            $lastActivitySecondsAgo = $now - $sessionMetadata->lastActivityTimestamp;
            if ($lastActivitySecondsAgo > $this->inactivityTimeout) {
                if ($sessionMetadata->lastActivityTimestamp !== null) {
                    $this->sessionKeyValueStore->remove($sessionMetadata->storageIdentifier);
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
     * @throws NotSupportedByBackendException
     * @throws CacheException
     */
    public function shutdownObject(): void
    {
        if (str_contains((string)$this->garbageCollectionProbability, '.')) {
            $decimals = strlen(strrchr((string)$this->garbageCollectionProbability, '.')) - 1;
            $factor = $decimals * 10;
        } else {
            $factor = 1;
        }
        if (rand(1, 100 * $factor) <= ($this->garbageCollectionProbability * $factor)) {
            $this->collectGarbage();
        }
    }
}

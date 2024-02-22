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
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Session\Data\SessionIdentifier;
use Neos\Flow\Session\Data\SessionKeyValueStore;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Flow\Http\Cookie;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Session\Data\StorageIdentifier;
use Psr\Log\LoggerInterface;

/**
 * A modular session implementation based on the caching framework.
 *
 * You may access the currently active session in userland code. In order to do this,
 * inject SessionInterface and NOT just the Session object.
 * The former will be a unique instance (singleton) representing the current session
 * while the latter would be a completely new session instance!
 *
 * You can use the Session Manager for accessing sessions which are not currently
 * active.
 *
 * Note that Flow's bootstrap (that is, Neos\Flow\Core\Scripts) will try to resume
 * a possibly existing session automatically. If a session could be resumed during
 * that phase already, calling start() at a later stage will be a no-operation.
 *
 * @see SessionManager
 * @phpstan-consistent-constructor
 */
class Session implements CookieEnabledInterface
{
    private const FLOW_OBJECT_STORAGE_KEY = 'Neos_Flow_Object_ObjectManager';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Meta data cache for this session
     *
     * @Flow\Inject
     * @var SessionMetaDataStore
     */
    protected $sessionMetaDataStore;

    /**
     * Storage cache for this session
     *
     * @Flow\Inject
     * @var SessionKeyValueStore
     */
    protected $sessionKeyValueStore;

    /**
     * @var string
     */
    protected $sessionCookieName;

    /**
     * @var integer
     */
    protected $sessionCookieLifetime = 0;

    /**
     * @var string
     */
    protected $sessionCookieDomain;

    /**
     * @var string
     */
    protected $sessionCookiePath;

    /**
     * @var boolean
     */
    protected $sessionCookieSecure = true;

    /**
     * @var boolean
     */
    protected $sessionCookieHttpOnly = true;

    /**
     * @var string
     */
    protected $sessionCookieSameSite;

    /**
     * @var Cookie
     */
    protected $sessionCookie;

    /**
     * @var integer
     */
    protected $inactivityTimeout;


    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var integer
     */
    protected $now;

    protected SessionMetaData|null $sessionMetaData = null;

    /**
     * If this session has been started
     */
    protected bool $started = false;

    /**
     * If this session is remote or the "current" session
     */
    protected bool $remote = false;

    /**
     * Constructs this session
     *
     * Session instances MUST NOT be created manually! They should be retrieved via
     * the Session Manager or through dependency injection (use SessionInterface!).
     */
    public function __construct()
    {
        $this->now = time();
    }

    public static function create(): self
    {
        return new static();
    }

    public static function createRemote(string $sessionIdentifier, string $storageIdentifier, int $lastActivityTimestamp = null, array $tags): self
    {
        $session = new static();
        $session->sessionMetaData = new SessionMetaData(
            SessionIdentifier::createFromString($sessionIdentifier),
            StorageIdentifier::createFromString($storageIdentifier),
            $lastActivityTimestamp,
            $tags
        );
        $session->started = true;
        $session->remote = true;
        return $session;
    }

    /**
     * @param SessionMetaData $sessionMetaData
     * @return Session
     */
    public static function createRemoteFromSessionMetaData(SessionMetaData $sessionMetaData): self
    {
        $session = new static();
        $session->sessionMetaData = $sessionMetaData;
        $session->started = true;
        $session->remote = true;
        return $session;
    }

    /**
     * @param Cookie $sessionCookie
     * @param string $storageIdentifier
     * @param int $lastActivityTimestamp
     * @param array $tags
     * @return Session
     */
    public static function createFromCookieAndSessionInformation(Cookie $sessionCookie, string $storageIdentifier, int $lastActivityTimestamp, array $tags = []): self
    {
        $session = new static();
        $session->sessionMetaData = new SessionMetaData(
            SessionIdentifier::createFromString($sessionCookie->getValue()),
            StorageIdentifier::createFromString($storageIdentifier),
            $lastActivityTimestamp,
            $tags
        );
        $session->sessionCookie = $sessionCookie;
        return $session;
    }

    /**
     * Injects the Flow settings
     *
     * @param array $settings Settings of the Flow package
     */
    public function injectSettings(array $settings): void
    {
        $this->sessionCookieName = $settings['session']['name'];
        $this->sessionCookieLifetime = (integer)$settings['session']['cookie']['lifetime'];
        $this->sessionCookieDomain = $settings['session']['cookie']['domain'];
        $this->sessionCookiePath = $settings['session']['cookie']['path'];
        $this->sessionCookieSecure = (boolean)$settings['session']['cookie']['secure'];
        $this->sessionCookieHttpOnly = (boolean)$settings['session']['cookie']['httponly'];
        $this->sessionCookieSameSite = $settings['session']['cookie']['samesite'];
        $this->inactivityTimeout = (integer)$settings['session']['inactivityTimeout'];
    }

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     */
    public function injectLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return Cookie
     */
    public function getSessionCookie(): Cookie
    {
        return $this->sessionCookie;
    }

    /**
     * Tells if the session has been started already.
     *
     * @api
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Tells if the session is local (the current session bound to the current HTTP
     * request) or remote (retrieved through the Session Manager).
     *
     * @return boolean true if the session is remote, false if this is the current session
     * @api
     */
    public function isRemote(): bool
    {
        return $this->remote;
    }

    /**
     * Starts the session, if it has not been already started
     *
     * @throws \Exception
     * @see CookieEnabledInterface
     * @api
     */
    public function start(): void
    {
        if ($this->started === false) {
            $this->sessionMetaData = SessionMetaData::createWithTimestamp($this->now);
            $this->sessionCookie = new Cookie($this->sessionCookieName, $this->sessionMetaData->sessionIdentifier->value, 0, $this->sessionCookieLifetime, $this->sessionCookieDomain, $this->sessionCookiePath, $this->sessionCookieSecure, $this->sessionCookieHttpOnly, $this->sessionCookieSameSite);
            $this->started = true;

            $this->writeSessionMetaDataCacheEntry();
        }
    }

    /**
     * Returns true if there is a session that can be resumed.
     *
     * If a to-be-resumed session was inactive for too long, this function will
     * trigger the expiration of that session. An expired session cannot be resumed.
     *
     * NOTE that this method does a bit more than the name implies: Because the
     * session info data needs to be loaded, this method stores this data already
     * so it doesn't have to be loaded again once the session is being used.
     *
     * @api
     */
    public function canBeResumed(): bool
    {
        if ($this->sessionCookie === null || $this->started === true) {
            return false;
        }
        $sessionIdentifier = $this->sessionCookie->getValue();
        if ($this->sessionMetaDataStore->isValidSessionIdentifier($sessionIdentifier) === false) {
            $this->logger->warning('SESSION IDENTIFIER INVALID: ' . $sessionIdentifier, LogEnvironment::fromMethodName(__METHOD__));
            return false;
        }
        $sessionMetaData = $this->sessionMetaDataStore->retrieve(SessionIdentifier::createFromString($sessionIdentifier));
        if ($sessionMetaData === null) {
            return false;
        }
        $this->sessionMetaData = $sessionMetaData;
        return !$this->autoExpire();
    }

    /**
     * Resumes an existing session, if any.
     *
     * @return null|integer If a session was resumed, the inactivity of this session since the last request is returned
     * @api
     */
    public function resume(): null|int
    {
        if ($this->started === false && $this->canBeResumed()) {
            $this->started = true;

            $sessionObjects = $this->sessionKeyValueStore->retrieve($this->sessionMetaData->storageIdentifier, self::FLOW_OBJECT_STORAGE_KEY);
            if (is_array($sessionObjects)) {
                foreach ($sessionObjects as $object) {
                    if ($object instanceof ProxyInterface) {
                        $objectName = $this->objectManager->getObjectNameByClassName(get_class($object));
                        if ($objectName && $this->objectManager->getScope($objectName) === ObjectConfiguration::SCOPE_SESSION) {
                            $this->objectManager->setInstance($objectName, $object);
                            $this->objectManager->get(Aspect\LazyLoadingAspect::class)->registerSessionInstance($objectName, $object);
                        }
                    }
                }
            } else {
                // Fallback for some malformed session data, if it is no array but something else.
                // In this case, we reset all session objects (graceful degradation).
                $this->sessionKeyValueStore->store($this->sessionMetaData->storageIdentifier, self::FLOW_OBJECT_STORAGE_KEY, []);
            }

            $lastActivitySecondsAgo = ($this->now - $this->sessionMetaData->lastActivityTimestamp);
            $this->sessionMetaData = $this->sessionMetaData->withLastActivityTimestamp($this->now);
            return $lastActivitySecondsAgo;
        }
        return null;
    }

    /**
     * Returns the current session identifier
     *
     * @return string The current session identifier
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getId(): string
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to retrieve the session identifier, but the session has not been started yet.)', 1351171517);
        }
        return $this->sessionMetaData->sessionIdentifier->value;
    }

    /**
     * Generates and propagates a new session ID and transfers all existing data
     * to the new session.
     *
     * @return string The new session ID
     * @throws Exception\SessionNotStartedException
     * @throws Exception\OperationNotSupportedException
     * @api
     */
    public function renewId(): string
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to renew the session identifier, but the session has not been started yet.', 1351182429);
        }
        if ($this->remote === true) {
            throw new Exception\OperationNotSupportedException(sprintf('Tried to renew the session identifier on a remote session (%s).', $this->sessionMetaData->sessionIdentifier->value), 1354034230);
        }

        $this->sessionMetaDataStore->remove($this->sessionMetaData);
        $this->sessionMetaData = $this->sessionMetaData->withNewSessionIdentifier();
        $this->writeSessionMetaDataCacheEntry();

        $this->sessionCookie->setValue($this->sessionMetaData->sessionIdentifier->value);
        return $this->sessionMetaData->sessionIdentifier->value;
    }

    /**
     * Returns the data associated with the given key.
     *
     * @param string $key An identifier for the content stored in the session.
     * @return mixed The contents associated with the given key
     * @throws Exception\SessionNotStartedException
     */
    public function getData(string $key): mixed
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to get session data, but the session has not been started yet.', 1351162255);
        }
        return $this->sessionKeyValueStore->retrieve($this->sessionMetaData->storageIdentifier, $key);
    }

    /**
     * Returns true if a session data entry $key is available.
     *
     * @param string $key Entry identifier of the session data
     * @return boolean
     * @throws Exception\SessionNotStartedException
     */
    public function hasKey(string $key): bool
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to check a session data entry, but the session has not been started yet.', 1352488661);
        }
        return $this->sessionKeyValueStore->has($this->sessionMetaData->storageIdentifier, $key);
    }

    /**
     * Stores the given data under the given key in the session
     *
     * @param string $key The key under which the data should be stored
     * @param mixed $data The data to be stored
     * @throws Exception\DataNotSerializableException
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function putData(string $key, mixed $data): void
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to create a session data entry, but the session has not been started yet.', 1351162259);
        }
        if (is_resource($data)) {
            throw new Exception\DataNotSerializableException('The given data cannot be stored in a session, because it is of type "' . gettype($data) . '".', 1351162262);
        }
        $this->sessionKeyValueStore->store($this->sessionMetaData->storageIdentifier, $key, $data);
    }

    /**
     * Returns the unix time stamp marking the last point in time this session has
     * been in use.
     *
     * For the current (local) session, this method will always return the current
     * time. For a remote session, the unix timestamp will be returned.
     *
     * @return integer unix timestamp
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getLastActivityTimestamp(): int
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to retrieve the last activity timestamp of a session which has not been started yet.', 1354290378);
        }
        return $this->sessionMetaData->lastActivityTimestamp;
    }

    /**
     * Tags this session with the given tag.
     *
     * Note that third-party libraries might also tag your session. Therefore it is
     * recommended to use namespaced tags such as "Acme-Demo-MySpecialTag".
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @throws Exception\SessionNotStartedException
     * @throws \InvalidArgumentException
     * @api
     */
    public function addTag(string $tag): void
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to tag a session which has not been started yet.', 1355143533);
        }
        if (!$this->sessionMetaDataStore->isValidSessionTag($tag)) {
            throw new \InvalidArgumentException(sprintf('The tag used for tagging session %s contained invalid characters. Make sure it matches this regular expression: "%s"', $this->sessionMetaData->sessionIdentifier->value, FrontendInterface::PATTERN_TAG));
        }
        $this->sessionMetaData = $this->sessionMetaData->withAddedTag($tag);
    }

    /**
     * Removes the specified tag from this session.
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function removeTag(string $tag): void
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to tag a session which has not been started yet.', 1355150140);
        }
        $this->sessionMetaData = $this->sessionMetaData->withRemovedTag($tag);
    }


    /**
     * Returns the tags this session has been tagged with.
     *
     * @return string[] The tags or an empty array if there aren't any
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getTags(): array
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to retrieve tags from a session which has not been started yet.', 1355141501);
        }
        return $this->sessionMetaData->tags;
    }

    /**
     * Updates the last activity time to "now".
     *
     * @throws Exception\SessionNotStartedException
     */
    public function touch(): void
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to touch a session, but the session has not been started yet.', 1354284318);
        }

        // Only makes sense for remote sessions because the currently active session
        // will be updated on shutdown anyway:
        if ($this->remote === true) {
            $this->sessionMetaData = $this->sessionMetaData->withLastActivityTimestamp($this->now);
            $this->writeSessionMetaDataCacheEntry();
        }
    }

    /**
     * Explicitly writes and closes the session
     *
     * @api
     */
    public function close(): void
    {
        $this->shutdownObject();
    }

    /**
     * Explicitly destroys all session data
     *
     * @param string $reason A reason for destroying the session – used by the LoggingAspect
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function destroy(string $reason = null): void
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to destroy a session which has not been started yet.', 1351162668);
        }

        if ($this->remote !== true) {
            $this->sessionCookie->expire();
        }

        $this->sessionMetaDataStore->remove($this->sessionMetaData);
        $this->sessionKeyValueStore->remove($this->sessionMetaData->storageIdentifier);
        $this->sessionMetaData = null;
        $this->started = false;
    }

    /**
     * Shuts down this session
     *
     * This method must not be called manually – it is invoked by Flow's object
     * management.
     *
     * @throws Exception\DataNotSerializableException
     * @throws Exception\SessionNotStartedException
     * @throws NotSupportedByBackendException
     * @throws \Neos\Cache\Exception
     */
    public function shutdownObject(): void
    {
        if ($this->started === true && $this->remote === false) {
            if ($this->sessionMetaDataStore->has($this->sessionMetaData->sessionIdentifier)) {
                $this->sessionKeyValueStore->store($this->sessionMetaData->storageIdentifier, self::FLOW_OBJECT_STORAGE_KEY, $this->objectManager->getSessionInstances() ?? []);
                $this->writeSessionMetaDataCacheEntry();
            }
            $this->started = false;
        }
    }

    /**
     * Automatically expires the session if the user has been inactive for too long.
     *
     * @return boolean true if the session expired, false if not
     */
    protected function autoExpire(): bool
    {
        $lastActivitySecondsAgo = $this->now - $this->sessionMetaData->lastActivityTimestamp;
        $expired = false;
        if ($this->inactivityTimeout !== 0 && $lastActivitySecondsAgo > $this->inactivityTimeout) {
            $this->started = true;
            $this->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $this->sessionMetaData->sessionIdentifier->value, $lastActivitySecondsAgo, $this->inactivityTimeout));
            $expired = true;
        }
        return $expired;
    }

    /**
     * Writes the cache entry containing information about the session, such as the
     * last activity time and the storage identifier.
     *
     * This function does not write the whole session _data_ into the storage cache,
     * but only the "head" cache entry containing meta information.
     *
     * The session cache entry is also tagged with "session", the session identifier
     * and any custom tags of this session, prefixed with TAG_PREFIX.
     *
     */
    protected function writeSessionMetaDataCacheEntry(): void
    {
        $this->sessionMetaDataStore->store($this->sessionMetaData);
    }
}

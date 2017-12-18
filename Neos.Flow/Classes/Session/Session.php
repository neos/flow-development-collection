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

use Neos\Cache\Backend\IterableBackendInterface;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Http;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Cache\Frontend\FrontendInterface;

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
 */
class Session implements SessionInterface
{
    const TAG_PREFIX = 'customtag-';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * Meta data cache for this session
     *
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $metaDataCache;

    /**
     * Storage cache for this session
     *
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $storageCache;

    /**
     * Bootstrap for retrieving the current HTTP request
     *
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

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
     * @var Http\Cookie
     */
    protected $sessionCookie;

    /**
     * @var integer
     */
    protected $inactivityTimeout;

    /**
     * @var integer
     */
    protected $lastActivityTimestamp;

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var integer
     */
    protected $now;

    /**
     * @var float
     */
    protected $garbageCollectionProbability;

    /**
     * @var integer
     */
    protected $garbageCollectionMaximumPerRun;

    /**
     * The session identifier
     *
     * @var string
     */
    protected $sessionIdentifier;

    /**
     * Internal identifier used for storing session data in the cache
     *
     * @var string
     */
    protected $storageIdentifier;

    /**
     * If this session has been started
     *
     * @var boolean
     */
    protected $started = false;

    /**
     * If this session is remote or the "current" session
     *
     * @var boolean
     */
    protected $remote = false;

    /**
     * @var Http\Request
     */
    protected $request;

    /**
     * @var Http\Response
     */
    protected $response;

    /**
     * Constructs this session
     *
     * If $sessionIdentifier is specified, this constructor will create a session
     * instance representing a remote session. In that case $storageIdentifier and
     * $lastActivityTimestamp are also required arguments.
     *
     * Session instances MUST NOT be created manually! They should be retrieved via
     * the Session Manager or through dependency injection (use SessionInterface!).
     *
     * @param string $sessionIdentifier The public session identifier which is also used in the session cookie
     * @param string $storageIdentifier The private storage identifier which is used for storage cache entries
     * @param integer $lastActivityTimestamp Unix timestamp of the last known activity for this session
     * @param array $tags A list of tags set for this session
     * @throws \InvalidArgumentException
     */
    public function __construct($sessionIdentifier = null, $storageIdentifier = null, $lastActivityTimestamp = null, array $tags = [])
    {
        if ($sessionIdentifier !== null) {
            if ($storageIdentifier === null || $lastActivityTimestamp === null) {
                throw new \InvalidArgumentException('Session requires a storage identifier and last activity timestamp for remote sessions.', 1354045988);
            }
            $this->sessionIdentifier = $sessionIdentifier;
            $this->storageIdentifier = $storageIdentifier;
            $this->lastActivityTimestamp = $lastActivityTimestamp;
            $this->started = true;
            $this->remote = true;
            $this->tags = $tags;
        }
        $this->now = time();
    }

    /**
     * Injects the Flow settings
     *
     * @param array $settings Settings of the Flow package
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->sessionCookieName = $settings['session']['name'];
        $this->sessionCookieLifetime = (integer)$settings['session']['cookie']['lifetime'];
        $this->sessionCookieDomain = $settings['session']['cookie']['domain'];
        $this->sessionCookiePath = $settings['session']['cookie']['path'];
        $this->sessionCookieSecure = (boolean)$settings['session']['cookie']['secure'];
        $this->sessionCookieHttpOnly = (boolean)$settings['session']['cookie']['httponly'];
        $this->garbageCollectionProbability = $settings['session']['garbageCollection']['probability'];
        $this->garbageCollectionMaximumPerRun = $settings['session']['garbageCollection']['maximumPerRun'];
        $this->inactivityTimeout = (integer)$settings['session']['inactivityTimeout'];
    }

    /**
     * @return void
     * @throws InvalidBackendException
     */
    public function initializeObject()
    {
        if (!$this->metaDataCache->getBackend() instanceof IterableBackendInterface) {
            throw new InvalidBackendException(sprintf('The session meta data cache must provide a backend implementing the IterableBackendInterface, but the given backend "%s" does not implement it.', get_class($this->metaDataCache->getBackend())), 1370964557);
        }
        if (!$this->storageCache->getBackend() instanceof IterableBackendInterface) {
            throw new InvalidBackendException(sprintf('The session storage cache must provide a backend implementing the IterableBackendInterface, but the given backend "%s" does not implement it.', get_class($this->storageCache->getBackend())), 1370964558);
        }
    }

    /**
     * Tells if the session has been started already.
     *
     * @return boolean
     * @api
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Tells if the session is local (the current session bound to the current HTTP
     * request) or remote (retrieved through the Session Manager).
     *
     * @return boolean TRUE if the session is remote, FALSE if this is the current session
     * @api
     */
    public function isRemote()
    {
        return $this->remote;
    }

    /**
     * Starts the session, if it has not been already started
     *
     * @return void
     * @api
     * @throws Exception\InvalidRequestHandlerException
     */
    public function start()
    {
        if ($this->request === null) {
            $requestHandler = $this->bootstrap->getActiveRequestHandler();
            if (!$requestHandler instanceof HttpRequestHandlerInterface) {
                throw new Exception\InvalidRequestHandlerException('Could not start a session because the currently active request handler (%s) is not an HTTP Request Handler.', 1364367520);
            }
            $this->initializeHttpAndCookie($requestHandler);
        }
        if ($this->started === false) {
            $this->sessionIdentifier = Algorithms::generateRandomString(32);
            $this->storageIdentifier = Algorithms::generateUUID();
            $this->sessionCookie = new Http\Cookie($this->sessionCookieName, $this->sessionIdentifier, 0, $this->sessionCookieLifetime, $this->sessionCookieDomain, $this->sessionCookiePath, $this->sessionCookieSecure, $this->sessionCookieHttpOnly);
            $this->response->setCookie($this->sessionCookie);
            $this->lastActivityTimestamp = $this->now;
            $this->started = true;

            $this->writeSessionMetaDataCacheEntry();
        }
    }

    /**
     * Returns TRUE if there is a session that can be resumed.
     *
     * If a to-be-resumed session was inactive for too long, this function will
     * trigger the expiration of that session. An expired session cannot be resumed.
     *
     * NOTE that this method does a bit more than the name implies: Because the
     * session info data needs to be loaded, this method stores this data already
     * so it doesn't have to be loaded again once the session is being used.
     *
     * @return boolean
     * @api
     */
    public function canBeResumed()
    {
        if ($this->request === null) {
            $this->initializeHttpAndCookie($this->bootstrap->getActiveRequestHandler());
        }
        if ($this->sessionCookie === null || $this->request === null || $this->started === true) {
            return false;
        }
        $sessionMetaData = $this->metaDataCache->get($this->sessionCookie->getValue());
        if ($sessionMetaData === false) {
            return false;
        }
        $this->lastActivityTimestamp = $sessionMetaData['lastActivityTimestamp'];
        $this->storageIdentifier = $sessionMetaData['storageIdentifier'];
        $this->tags = $sessionMetaData['tags'];
        return !$this->autoExpire();
    }

    /**
     * Resumes an existing session, if any.
     *
     * @return integer If a session was resumed, the inactivity of since the last request is returned
     * @api
     */
    public function resume()
    {
        if ($this->started === false && $this->canBeResumed()) {
            $this->sessionIdentifier = $this->sessionCookie->getValue();
            $this->response->setCookie($this->sessionCookie);
            $this->started = true;

            $sessionObjects = $this->storageCache->get($this->storageIdentifier . md5('Neos_Flow_Object_ObjectManager'));
            if (is_array($sessionObjects)) {
                foreach ($sessionObjects as $object) {
                    if ($object instanceof ProxyInterface) {
                        $objectName = $this->objectManager->getObjectNameByClassName(get_class($object));
                        if ($this->objectManager->getScope($objectName) === ObjectConfiguration::SCOPE_SESSION) {
                            $this->objectManager->setInstance($objectName, $object);
                            $this->objectManager->get(Aspect\LazyLoadingAspect::class)->registerSessionInstance($objectName, $object);
                        }
                    }
                }
            } else {
                // Fallback for some malformed session data, if it is no array but something else.
                // In this case, we reset all session objects (graceful degradation).
                $this->storageCache->set($this->storageIdentifier . md5('Neos_Flow_Object_ObjectManager'), [], [$this->storageIdentifier], 0);
            }

            $lastActivitySecondsAgo = ($this->now - $this->lastActivityTimestamp);
            $this->lastActivityTimestamp = $this->now;
            return $lastActivitySecondsAgo;
        }
    }

    /**
     * Returns the current session identifier
     *
     * @return string The current session identifier
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getId()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to retrieve the session identifier, but the session has not been started yet.)', 1351171517);
        }
        return $this->sessionIdentifier;
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
    public function renewId()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to renew the session identifier, but the session has not been started yet.', 1351182429);
        }
        if ($this->remote === true) {
            throw new Exception\OperationNotSupportedException(sprintf('Tried to renew the session identifier on a remote session (%s).', $this->sessionIdentifier), 1354034230);
        }

        $this->removeSessionMetaDataCacheEntry($this->sessionIdentifier);
        $this->sessionIdentifier = Algorithms::generateRandomString(32);
        $this->writeSessionMetaDataCacheEntry();

        $this->sessionCookie->setValue($this->sessionIdentifier);
        return $this->sessionIdentifier;
    }

    /**
     * Returns the data associated with the given key.
     *
     * @param string $key An identifier for the content stored in the session.
     * @return mixed The contents associated with the given key
     * @throws Exception\SessionNotStartedException
     */
    public function getData($key)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to get session data, but the session has not been started yet.', 1351162255);
        }
        return $this->storageCache->get($this->storageIdentifier . md5($key));
    }

    /**
     * Returns TRUE if a session data entry $key is available.
     *
     * @param string $key Entry identifier of the session data
     * @return boolean
     * @throws Exception\SessionNotStartedException
     */
    public function hasKey($key)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to check a session data entry, but the session has not been started yet.', 1352488661);
        }
        return $this->storageCache->has($this->storageIdentifier . md5($key));
    }

    /**
     * Stores the given data under the given key in the session
     *
     * @param string $key The key under which the data should be stored
     * @param mixed $data The data to be stored
     * @return void
     * @throws Exception\DataNotSerializableException
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function putData($key, $data)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to create a session data entry, but the session has not been started yet.', 1351162259);
        }
        if (is_resource($data)) {
            throw new Exception\DataNotSerializableException('The given data cannot be stored in a session, because it is of type "' . gettype($data) . '".', 1351162262);
        }
        $this->storageCache->set($this->storageIdentifier . md5($key), $data, [$this->storageIdentifier], 0);
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
    public function getLastActivityTimestamp()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to retrieve the last activity timestamp of a session which has not been started yet.', 1354290378);
        }
        return $this->lastActivityTimestamp;
    }

    /**
     * Tags this session with the given tag.
     *
     * Note that third-party libraries might also tag your session. Therefore it is
     * recommended to use namespaced tags such as "Acme-Demo-MySpecialTag".
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @return void
     * @throws Exception\SessionNotStartedException
     * @throws \InvalidArgumentException
     * @api
     */
    public function addTag($tag)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to tag a session which has not been started yet.', 1355143533);
        }
        if (!$this->metaDataCache->isValidTag($tag)) {
            throw new \InvalidArgumentException(sprintf('The tag used for tagging session %s contained invalid characters. Make sure it matches this regular expression: "%s"', $this->sessionIdentifier, FrontendInterface::PATTERN_TAG));
        }
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    /**
     * Removes the specified tag from this session.
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @return void
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function removeTag($tag)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to tag a session which has not been started yet.', 1355150140);
        }
        $index = array_search($tag, $this->tags);
        if ($index !== false) {
            unset($this->tags[$index]);
        }
    }


    /**
     * Returns the tags this session has been tagged with.
     *
     * @return array The tags or an empty array if there aren't any
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getTags()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to retrieve tags from a session which has not been started yet.', 1355141501);
        }
        return $this->tags;
    }

    /**
     * Updates the last activity time to "now".
     *
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function touch()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to touch a session, but the session has not been started yet.', 1354284318);
        }

        // Only makes sense for remote sessions because the currently active session
        // will be updated on shutdown anyway:
        if ($this->remote === true) {
            $this->lastActivityTimestamp = $this->now;
            $this->writeSessionMetaDataCacheEntry();
        }
    }

    /**
     * Explicitly writes and closes the session
     *
     * @return void
     * @api
     */
    public function close()
    {
        $this->shutdownObject();
    }

    /**
     * Explicitly destroys all session data
     *
     * @param string $reason A reason for destroying the session – used by the LoggingAspect
     * @return void
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function destroy($reason = null)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('Tried to destroy a session which has not been started yet.', 1351162668);
        }
        if ($this->remote !== true) {
            if (!$this->response->hasCookie($this->sessionCookieName)) {
                $this->response->setCookie($this->sessionCookie);
            }
            $this->sessionCookie->expire();
        }

        $this->removeSessionMetaDataCacheEntry($this->sessionIdentifier);
        $this->storageCache->flushByTag($this->storageIdentifier);
        $this->started = false;
        $this->sessionIdentifier = null;
        $this->storageIdentifier = null;
        $this->tags = [];
        $this->request = null;
    }

    /**
     * Iterates over all existing sessions and removes their data if the inactivity
     * timeout was reached.
     *
     * @return integer The number of outdated entries removed
     * @api
     */
    public function collectGarbage()
    {
        if ($this->inactivityTimeout === 0) {
            return 0;
        }
        if ($this->metaDataCache->has('_garbage-collection-running')) {
            return false;
        }

        $sessionRemovalCount = 0;
        $this->metaDataCache->set('_garbage-collection-running', true, [], 120);

        foreach ($this->metaDataCache->getIterator() as $sessionIdentifier => $sessionInfo) {
            if ($sessionIdentifier === '_garbage-collection-running') {
                continue;
            }
            $lastActivitySecondsAgo = $this->now - $sessionInfo['lastActivityTimestamp'];
            if ($lastActivitySecondsAgo > $this->inactivityTimeout) {
                if ($sessionInfo['storageIdentifier'] === null) {
                    $this->systemLogger->log('SESSION INFO INVALID: ' . $sessionIdentifier, LOG_WARNING, $sessionInfo);
                } else {
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
     */
    public function shutdownObject()
    {
        if ($this->started === true && $this->remote === false) {
            if ($this->metaDataCache->has($this->sessionIdentifier)) {
                // Security context can't be injected and must be retrieved manually
                // because it relies on this very session object:
                $securityContext = $this->objectManager->get(Context::class);
                if ($securityContext->isInitialized()) {
                    $this->storeAuthenticatedAccountsInfo($securityContext->getAuthenticationTokens());
                }

                $this->putData('Neos_Flow_Object_ObjectManager', $this->objectManager->getSessionInstances());
                $this->writeSessionMetaDataCacheEntry();
            }
            $this->started = false;

            $decimals = (integer)strlen(strrchr($this->garbageCollectionProbability, '.')) - 1;
            $factor = ($decimals > -1) ? $decimals * 10 : 1;
            if (rand(1, 100 * $factor) <= ($this->garbageCollectionProbability * $factor)) {
                $this->collectGarbage();
            }
        }
        $this->request = null;
    }

    /**
     * Automatically expires the session if the user has been inactive for too long.
     *
     * @return boolean TRUE if the session expired, FALSE if not
     */
    protected function autoExpire()
    {
        $lastActivitySecondsAgo = $this->now - $this->lastActivityTimestamp;
        $expired = false;
        if ($this->inactivityTimeout !== 0 && $lastActivitySecondsAgo > $this->inactivityTimeout) {
            $this->started = true;
            $this->sessionIdentifier = $this->sessionCookie->getValue();
            $this->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $this->sessionIdentifier, $lastActivitySecondsAgo, $this->inactivityTimeout));
            $expired = true;
        }
        return $expired;
    }

    /**
     * Initialize request, response and session cookie
     *
     * @param HttpRequestHandlerInterface $requestHandler
     * @return void
     * @throws Exception\InvalidRequestResponseException
     */
    protected function initializeHttpAndCookie(HttpRequestHandlerInterface $requestHandler)
    {
        $this->request = $requestHandler->getHttpRequest();
        $this->response = $requestHandler->getHttpResponse();

        if (!$this->request instanceof Http\Request || !$this->response instanceof Http\Response) {
            $className = get_class($requestHandler);
            $requestMessage = 'the request was ' . (is_object($this->request) ? 'of type ' . get_class($this->request) : gettype($this->request));
            $responseMessage = 'and the response was ' . (is_object($this->response) ? 'of type ' . get_class($this->response) : gettype($this->response));
            throw new Exception\InvalidRequestResponseException(sprintf('The active request handler "%s" did not provide a valid HTTP request / HTTP response pair: %s %s.', $className, $requestMessage, $responseMessage), 1354633950);
        }

        if ($this->request->hasCookie($this->sessionCookieName)) {
            $sessionIdentifier = $this->request->getCookie($this->sessionCookieName)->getValue();
            $this->sessionCookie = new Http\Cookie($this->sessionCookieName, $sessionIdentifier, 0, $this->sessionCookieLifetime, $this->sessionCookieDomain, $this->sessionCookiePath, $this->sessionCookieSecure, $this->sessionCookieHttpOnly);
        }
    }

    /**
     * Stores some information about the authenticated accounts in the session data.
     *
     * This method will check if a session has already been started, which is
     * the case after tokens relying on a session have been authenticated: the
     * UsernamePasswordToken does, for example, start a session in its authenticate()
     * method.
     *
     * Because more than one account can be authenticated at a time, this method
     * accepts an array of tokens instead of a single account.
     *
     * Note that if a session is started after tokens have been authenticated, the
     * session will NOT be tagged with authenticated accounts.
     *
     * @param array<TokenInterface>
     * @return void
     */
    protected function storeAuthenticatedAccountsInfo(array $tokens)
    {
        $accountProviderAndIdentifierPairs = [];
        /** @var TokenInterface $token */
        foreach ($tokens as $token) {
            $account = $token->getAccount();
            if ($token->isAuthenticated() && $account !== null) {
                $accountProviderAndIdentifierPairs[$account->getAuthenticationProviderName() . ':' . $account->getAccountIdentifier()] = true;
            }
        }
        if ($accountProviderAndIdentifierPairs !== []) {
            $this->putData('Neos_Flow_Security_Accounts', array_keys($accountProviderAndIdentifierPairs));
        }
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
     * @return void
     */
    protected function writeSessionMetaDataCacheEntry()
    {
        $sessionInfo = [
            'lastActivityTimestamp' => $this->lastActivityTimestamp,
            'storageIdentifier' => $this->storageIdentifier,
            'tags' => $this->tags
        ];

        $tagsForCacheEntry = array_map(function ($tag) {
            return Session::TAG_PREFIX . $tag;
        }, $this->tags);
        $tagsForCacheEntry[] = $this->sessionIdentifier;
        $tagsForCacheEntry[] = 'session';

        $this->metaDataCache->set($this->sessionIdentifier, $sessionInfo, $tagsForCacheEntry, 0);
    }

    /**
     * Removes the session info cache entry for the specified session.
     *
     * Note that this function does only remove the "head" cache entry, not the
     * related data referred to by the storage identifier.
     *
     * @param string $sessionIdentifier
     * @return void
     */
    protected function removeSessionMetaDataCacheEntry($sessionIdentifier)
    {
        $this->metaDataCache->remove($sessionIdentifier);
    }
}

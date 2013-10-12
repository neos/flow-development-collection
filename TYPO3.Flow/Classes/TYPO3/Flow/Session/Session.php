<?php
namespace TYPO3\Flow\Session;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cache\Backend\IterableBackendInterface;
use TYPO3\Flow\Cache\Exception\InvalidBackendException;
use TYPO3\Flow\Object\Configuration\Configuration as ObjectConfiguration;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Object\Proxy\ProxyInterface;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Cookie;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;

/**
 * A modular session implementation based on the caching framework.
 *
 * You may access the currently active session in userland code. In order to do this,
 * inject TYPO3\Flow\Session\SessionInterface and NOT just TYPO3\Flow\Session\Session.
 * The former will be a unique instance (singleton) representing the current session
 * while the latter would be a completely new session instance!
 *
 * You can use the Session Manager for accessing sessions which are not currently
 * active.
 *
 * Note that Flow's bootstrap (that is, TYPO3\Flow\Core\Scripts) will try to resume
 * a possibly existing session automatically. If a session could be resumed during
 * that phase already, calling start() at a later stage will be a no-operation.
 *
 * @see \TYPO3\Flow\Session\SessionManager
 */
class Session implements SessionInterface {

	const TAG_PREFIX = 'customtag-';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Meta data cache for this session
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $metaDataCache;

	/**
	 * Storage cache for this session
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $storageCache;

	/**
	 * Bootstrap for retrieving the current HTTP request
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Core\Bootstrap
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
	protected $sessionCookieSecure = TRUE;

	/**
	 * @var boolean
	 */
	protected $sessionCookieHttpOnly = TRUE;

	/**
	 * @var \TYPO3\Flow\Http\Cookie
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
	protected $tags = array();

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
	protected $started = FALSE;

	/**
	 * If this session is remote or the "current" session
	 *
	 * @var boolean
	 */
	protected $remote = FALSE;

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Http\Response
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
	public function __construct($sessionIdentifier = NULL, $storageIdentifier = NULL, $lastActivityTimestamp = NULL, array $tags = array()) {
		if ($sessionIdentifier !== NULL) {
			if ($storageIdentifier === NULL || $lastActivityTimestamp === NULL) {
				throw new \InvalidArgumentException('Session requires a storage identifier and last activity timestamp for remote sessions.', 1354045988);
			}
			$this->sessionIdentifier = $sessionIdentifier;
			$this->storageIdentifier = $storageIdentifier;
			$this->lastActivityTimestamp = $lastActivityTimestamp;
			$this->started = TRUE;
			$this->remote = TRUE;
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
	public function injectSettings(array $settings) {
		$this->sessionCookieName = $settings['session']['name'];
		$this->sessionCookieLifetime =  (integer)$settings['session']['cookie']['lifetime'];
		$this->sessionCookieDomain =  $settings['session']['cookie']['domain'];
		$this->sessionCookiePath =  $settings['session']['cookie']['path'];
		$this->sessionCookieSecure =  (boolean)$settings['session']['cookie']['secure'];
		$this->sessionCookieHttpOnly =  (boolean)$settings['session']['cookie']['httponly'];
		$this->garbageCollectionProbability = $settings['session']['garbageCollection']['probability'];
		$this->garbageCollectionMaximumPerRun = $settings['session']['garbageCollection']['maximumPerRun'];
		$this->inactivityTimeout = (integer)$settings['session']['inactivityTimeout'];
	}

	/**
	 * @return void
	 * @throws \TYPO3\Flow\Cache\Exception\InvalidBackendException
	 */
	public function initializeObject() {
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
	public function isStarted() {
		return $this->started;
	}

	/**
	 * Tells if the session is local (the current session bound to the current HTTP
	 * request) or remote (retrieved through the Session Manager).
	 *
	 * @return boolean TRUE if the session is remote, FALSE if this is the current session
	 * @api
	 */
	public function isRemote() {
		return $this->remote;
	}

	/**
	 * Starts the session, if it has not been already started
	 *
	 * @return void
	 * @api
	 */
	public function start() {
		if ($this->request === NULL) {
			$requestHandler = $this->bootstrap->getActiveRequestHandler();
			if (!$requestHandler instanceof HttpRequestHandlerInterface) {
				throw new \TYPO3\Flow\Session\Exception\InvalidRequestHandlerException('Could not start a session because the currently active request handler (%s) is not an HTTP Request Handler.', 1364367520);
			}
			$this->initializeHttpAndCookie($requestHandler);
		}
		if ($this->started === FALSE) {
			$this->sessionIdentifier = Algorithms::generateRandomString(32);
			$this->storageIdentifier = Algorithms::generateUUID();
			$this->sessionCookie = new Cookie($this->sessionCookieName, $this->sessionIdentifier, $this->sessionCookieLifetime, NULL, $this->sessionCookieDomain, $this->sessionCookiePath, $this->sessionCookieSecure, $this->sessionCookieHttpOnly);
			$this->response->setCookie($this->sessionCookie);
			$this->lastActivityTimestamp = $this->now;
			$this->started = TRUE;

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
	public function canBeResumed() {
		if ($this->request === NULL) {
			$this->initializeHttpAndCookie($this->bootstrap->getActiveRequestHandler());
		}
		if ($this->sessionCookie === NULL || $this->request === NULL || $this->started === TRUE) {
			return FALSE;
		}
		$sessionMetaData = $this->metaDataCache->get($this->sessionCookie->getValue());
		if ($sessionMetaData === FALSE) {
			return FALSE;
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
	public function resume() {
		if ($this->started === FALSE && $this->canBeResumed()) {
			$this->sessionIdentifier = $this->sessionCookie->getValue();
			$this->response->setCookie($this->sessionCookie);
			$this->started = TRUE;

			$sessionObjects = $this->storageCache->get($this->storageIdentifier . md5('TYPO3_Flow_Object_ObjectManager'));
			if (is_array($sessionObjects)) {
				foreach ($sessionObjects as $object) {
					if ($object instanceof ProxyInterface) {
						$objectName = $this->objectManager->getObjectNameByClassName(get_class($object));
						if ($this->objectManager->getScope($objectName) === ObjectConfiguration::SCOPE_SESSION) {
							$this->objectManager->setInstance($objectName, $object);
							$this->objectManager->get('TYPO3\Flow\Session\Aspect\LazyLoadingAspect')->registerSessionInstance($objectName, $object);
						}
					}
				}
			} else {
					// Fallback for some malformed session data, if it is no array but something else.
					// In this case, we reset all session objects (graceful degradation).
				$this->storageCache->set($this->storageIdentifier . md5('TYPO3_Flow_Object_ObjectManager'), array(), array($this->storageIdentifier), 0);
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
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function getId() {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to retrieve the session identifier, but the session has not been started yet.)', 1351171517);
		}
		return $this->sessionIdentifier;
	}

	/**
	 * Generates and propagates a new session ID and transfers all existing data
	 * to the new session.
	 *
	 * @return string The new session ID
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @throws \TYPO3\Flow\Session\Exception\OperationNotSupportedException
	 * @api
	 */
	public function renewId() {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to renew the session identifier, but the session has not been started yet.', 1351182429);
		}
		if ($this->remote === TRUE) {
			throw new \TYPO3\Flow\Session\Exception\OperationNotSupportedException(sprintf('Tried to renew the session identifier on a remote session (%s).', $this->sessionIdentifier), 1354034230);
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
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getData($key) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to get session data, but the session has not been started yet.', 1351162255);
		}
		return $this->storageCache->get($this->storageIdentifier . md5($key));
	}

	/**
	 * Returns TRUE if a session data entry $key is available.
	 *
	 * @param string $key Entry identifier of the session data
	 * @return boolean
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function hasKey($key) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to check a session data entry, but the session has not been started yet.', 1352488661);
		}
		return $this->storageCache->has($this->storageIdentifier . md5($key));
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param string $key The key under which the data should be stored
	 * @param mixed $data The data to be stored
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\DataNotSerializableException
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to create a session data entry, but the session has not been started yet.', 1351162259);
		}
		if (is_resource($data)) {
			throw new \TYPO3\Flow\Session\Exception\DataNotSerializableException('The given data cannot be stored in a session, because it is of type "' . gettype($data) . '".', 1351162262);
		}
		$this->storageCache->set($this->storageIdentifier . md5($key), $data, array($this->storageIdentifier), 0);
	}

	/**
	 * Returns the unix time stamp marking the last point in time this session has
	 * been in use.
	 *
	 * For the current (local) session, this method will always return the current
	 * time. For a remote session, the unix timestamp will be returned.
	 *
	 * @return integer unix timestamp
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function getLastActivityTimestamp() {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to retrieve the last activity timestamp of a session which has not been started yet.', 1354290378);
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
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function addTag($tag) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to tag a session which has not been started yet.', 1355143533);
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
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function removeTag($tag) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to tag a session which has not been started yet.', 1355150140);
		}
		$index = array_search($tag, $this->tags);
		if ($index !== FALSE) {
			unset($this->tags[$index]);
		}
	}


	/**
	 * Returns the tags this session has been tagged with.
	 *
	 * @return array The tags or an empty array if there aren't any
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function getTags() {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to retrieve tags from a session which has not been started yet.', 1355141501);
		}
		return $this->tags;
	}

	/**
	 * Updates the last activity time to "now".
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function touch() {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to touch a session, but the session has not been started yet.', 1354284318);
		}

			// Only makes sense for remote sessions because the currently active session
			// will be updated on shutdown anyway:
		if ($this->remote === TRUE) {
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
	public function close() {
		$this->shutdownObject();
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @param string $reason A reason for destroying the session – used by the LoggingAspect
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function destroy($reason = NULL) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to destroy a session which has not been started yet.', 1351162668);
		}
		if ($this->remote !== TRUE) {
			if (!$this->response->hasCookie($this->sessionCookieName)) {
				$this->response->setCookie($this->sessionCookie);
			}
			$this->sessionCookie->expire();
		}

		$this->removeSessionMetaDataCacheEntry($this->sessionIdentifier);
		$this->storageCache->flushByTag($this->storageIdentifier);
		$this->started = FALSE;
		$this->sessionIdentifier = NULL;
		$this->storageIdentifier = NULL;
		$this->tags = array();
		$this->request = NULL;
	}

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Iterates over all existing sessions and removes their data if the inactivity
	 * timeout was reached.
	 *
	 * @return integer The number of outdated entries removed
	 * @api
	 */
	public function collectGarbage() {
		if ($this->inactivityTimeout === 0) {
			return 0;
		}
		if ($this->metaDataCache->has('_garbage-collection-running')) {
			return FALSE;
		}

		$sessionRemovalCount = 0;
		$this->metaDataCache->set('_garbage-collection-running', TRUE, array(), 120);

		foreach ($this->metaDataCache->getIterator() as $sessionIdentifier => $sessionInfo) {
			if ($sessionIdentifier === '_garbage-collection-running') {
				continue;
			}
			$lastActivitySecondsAgo = $this->now - $sessionInfo['lastActivityTimestamp'];
			if ($lastActivitySecondsAgo > $this->inactivityTimeout) {
				if ($sessionInfo['storageIdentifier'] === NULL) {
					$this->systemLogger->log('SESSION INFO INVALID: ' . $sessionIdentifier, LOG_WARNING, $sessionInfo);
				} else {
					$this->storageCache->flushByTag($sessionInfo['storageIdentifier']);
					$sessionRemovalCount ++;
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
	public function shutdownObject() {
		if ($this->started === TRUE && $this->remote === FALSE) {

			if ($this->metaDataCache->has($this->sessionIdentifier)) {
					// Security context can't be injected and must be retrieved manually
					// because it relies on this very session object:
				$securityContext = $this->objectManager->get('TYPO3\Flow\Security\Context');
				if ($securityContext->isInitialized()) {
					$this->storeAuthenticatedAccountsInfo($securityContext->getAuthenticationTokens());
				}

				$this->putData('TYPO3_Flow_Object_ObjectManager', $this->objectManager->getSessionInstances());
				$this->writeSessionMetaDataCacheEntry();
			}
			$this->started = FALSE;

			$decimals = strlen(strrchr($this->garbageCollectionProbability, '.')) - 1;
			$factor = ($decimals > -1) ? $decimals * 10 : 1;
			if (rand(0, 100 * $factor) <= ($this->garbageCollectionProbability * $factor)) {
				$this->collectGarbage();
			}
		}
		$this->request = NULL;
	}

	/**
	 * Automatically expires the session if the user has been inactive for too long.
	 *
	 * @return boolean TRUE if the session expired, FALSE if not
	 */
	protected function autoExpire() {
		$lastActivitySecondsAgo = $this->now - $this->lastActivityTimestamp;
		$expired = FALSE;
		if ($this->inactivityTimeout !== 0 && $lastActivitySecondsAgo > $this->inactivityTimeout) {
			$this->started = TRUE;
			$this->sessionIdentifier = $this->sessionCookie->getValue();
			$this->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $this->sessionIdentifier, $lastActivitySecondsAgo, $this->inactivityTimeout));
			$expired = TRUE;
		}
		return $expired;
	}

	/**
	 * Initialize request, response and session cookie
	 *
	 * @param \TYPO3\Flow\Http\HttpRequestHandlerInterface $requestHandler
	 * @return void
	 */
	protected function initializeHttpAndCookie(HttpRequestHandlerInterface $requestHandler) {
		$this->request = $requestHandler->getHttpRequest();
		$this->response = $requestHandler->getHttpResponse();

		if (!$this->request instanceof Request || !$this->response instanceof Response) {
			$className = get_class($requestHandler);
			$requestMessage = 'the request was ' . (is_object($this->request) ? 'of type ' . get_class($this->request) : gettype($this->request));
			$responseMessage = 'and the response was ' . (is_object($this->response) ? 'of type ' . get_class($this->response) : gettype($this->response));
			throw new \TYPO3\Flow\Session\Exception\InvalidRequestResponseException(sprintf('The active request handler "%s" did not provide a valid HTTP request / HTTP response pair: %s %s.', $className, $requestMessage, $responseMessage), 1354633950);
		}

		if ($this->request->hasCookie($this->sessionCookieName)) {
			$sessionIdentifier = $this->request->getCookie($this->sessionCookieName)->getValue();
			$this->sessionCookie = new Cookie($this->sessionCookieName, $sessionIdentifier, $this->sessionCookieLifetime, NULL, $this->sessionCookieDomain, $this->sessionCookiePath, $this->sessionCookieSecure, $this->sessionCookieHttpOnly);
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
	 * @param array<\TYPO3\Flow\Security\Authentication\TokenInterface>
	 * @return void
	 */
	protected function storeAuthenticatedAccountsInfo(array $tokens) {
		$accountProviderAndIdentifierPairs = array();
		foreach ($tokens as $token) {
			$account = $token->getAccount();
			if ($token->isAuthenticated() && $account !== NULL) {
				$accountProviderAndIdentifierPairs[$account->getAuthenticationProviderName() . ':' . $account->getAccountIdentifier()] = TRUE;
			}
		}
		if ($accountProviderAndIdentifierPairs !== array()) {
			$this->putData('TYPO3_Flow_Security_Accounts', array_keys($accountProviderAndIdentifierPairs));
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
	protected function writeSessionMetaDataCacheEntry() {
		$sessionInfo = array(
			'lastActivityTimestamp' => $this->lastActivityTimestamp,
			'storageIdentifier' => $this->storageIdentifier,
			'tags' => $this->tags
		);

		$tagsForCacheEntry = array_map(function($tag) {
			return Session::TAG_PREFIX . $tag;
		}, $this->tags);
		$tagsForCacheEntry[] = $this->sessionIdentifier;

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
	protected function removeSessionMetaDataCacheEntry($sessionIdentifier) {
		$this->metaDataCache->remove($sessionIdentifier);
	}
}

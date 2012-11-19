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

use TYPO3\Flow\Object\Configuration\Configuration as ObjectConfiguration;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Cookie;

/**
 * A modular session implementation based on the caching framework.
 *
 * @Flow\Scope("singleton")
 */
class FlowSession implements \TYPO3\Flow\Session\SessionInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\Aspect\LazyLoadingAspect
	 */
	protected $lazyLoadingAspect;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Cache storage for this session
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

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
	 * @var float
	 */
	protected $garbageCollectionProbability;

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
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Http\Response
	 */
	protected $response;

	/**
	 * Injects the Flow settings
	 *
	 * @param array $settings Settings of the Flow package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->sessionCookieName = $settings['session']['FlowSession']['name'];
		$this->sessionCookieLifetime =  (integer)$settings['session']['FlowSession']['cookie']['lifetime'];
		$this->sessionCookieDomain =  $settings['session']['FlowSession']['cookie']['domain'];
		$this->sessionCookiePath =  $settings['session']['FlowSession']['cookie']['path'];
		$this->sessionCookieSecure =  (boolean)$settings['session']['FlowSession']['cookie']['secure'];
		$this->sessionCookieHttpOnly =  (boolean)$settings['session']['FlowSession']['cookie']['httponly'];
		$this->garbageCollectionProbability = $settings['session']['FlowSession']['garbageCollectionProbability'];
		$this->inactivityTimeout = (integer)$settings['session']['inactivityTimeout'];
	}

	/**
	 * Initialize request, response and session cookie
	 *
	 * @return void
	 */
	public function initializeObject() {
		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof HttpRequestHandlerInterface) {
			$this->request = $requestHandler->getHttpRequest();
			$this->response = $requestHandler->getHttpResponse();

			if ($this->request->hasCookie($this->sessionCookieName)) {
				$this->sessionCookie = $this->request->getCookie($this->sessionCookieName);
			}
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
	 * Starts the session, if it has not been already started
	 *
	 * @return void
	 * @api
	 */
	public function start() {
		if ($this->started === FALSE && $this->request !== NULL) {
			$this->sessionIdentifier = Algorithms::generateRandomString(32);
			$this->storageIdentifier = Algorithms::generateUUID();
			$this->sessionCookie = new Cookie($this->sessionCookieName, $this->sessionIdentifier, $this->sessionCookieLifetime, NULL, $this->sessionCookieDomain, $this->sessionCookiePath, $this->sessionCookieSecure, $this->sessionCookieHttpOnly);

			$this->response->setCookie($this->sessionCookie);
			$this->started = TRUE;
		}
	}

	/**
	 * Returns TRUE if there is a session that can be resumed.
	 *
	 * If a to-be-resumed session was inactive for too long, this function will
	 * trigger the expiration of that session. An expired session cannot be resumed.
	 *
	 * @return boolean
	 * @api
	 */
	public function canBeResumed() {
		if ($this->sessionCookie === NULL || $this->request === NULL) {
			return FALSE;
		}
		$sessionInfo = $this->cache->get($this->sessionCookie->getValue());
		if ($sessionInfo === FALSE) {
			return FALSE;
		}
		$this->lastActivityTimestamp = $sessionInfo['lastActivityTimestamp'];
		$this->storageIdentifier = $sessionInfo['storageIdentifier'];
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

			$sessionObjects = $this->cache->get($this->storageIdentifier . md5('TYPO3_Flow_Object_ObjectManager'));
			if (is_array($sessionObjects)) {
				foreach ($sessionObjects as $object) {
					if ($object instanceof \TYPO3\Flow\Object\Proxy\ProxyInterface) {
						$objectName = $this->objectManager->getObjectNameByClassName(get_class($object));
						if ($this->objectManager->getScope($objectName) === ObjectConfiguration::SCOPE_SESSION) {
							$this->objectManager->setInstance($objectName, $object);
							$this->lazyLoadingAspect->registerSessionInstance($objectName, $object);
							$object->__wakeup();
						}
					}
				}
			} else {
					// Fallback for some malformed session data, if it is no array but something else.
					// In this case, we reset all session objects (graceful degradation).
				$this->cache->set($this->storageIdentifier . md5('TYPO3_Flow_Object_ObjectManager'), array(), array($this->storageIdentifier), 0);
			}

			return (time() - $this->lastActivityTimestamp);
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
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to retrieve the session identifier, but the session has not been started yet.', 1351171517);
		}
		return $this->sessionIdentifier;
	}

	/**
	 * Generates and propagates a new session ID and transfers all existing data
	 * to the new session.
	 *
	 * @return string The new session ID
	 * @api
	 */
	public function renewId() {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to renew the session identifier, but the session has not been started yet.', 1351182429);
		}

		$this->sessionIdentifier = Algorithms::generateRandomString(32);
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
		return $this->cache->get($this->storageIdentifier . md5($key));
	}

	/**
	 * Returns TRUE if a session data entry $key is available.
	 *
	 * @param string $key Entry identifier of the session data
	 * @return boolean
	 */
	public function hasKey($key) {
		if ($this->started !== TRUE) {
			throw new \TYPO3\Flow\Session\Exception\SessionNotStartedException('Tried to check a session data entry, but the session has not been started yet.', 1352488661);
		}
		return $this->cache->has($this->storageIdentifier . md5($key));
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
		$this->cache->set($this->storageIdentifier . md5($key), $data, array($this->storageIdentifier), 0);
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

		if (!$this->response->hasCookie($this->sessionCookieName)) {
			$this->response->setCookie($this->sessionCookie);
		}
		$this->sessionCookie->expire();

		$this->cache->flushByTag($this->storageIdentifier);
		$this->started = FALSE;
		$this->sessionIdentifier = NULL;
		$this->storageIdentifier = NULL;
	}

	/**
	 * Destroys data from all active and inactive sessions.
	 *
	 * TODO Implement
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return integer The number of sessions which have been removed
	 */
	static public function destroyAll(Bootstrap $bootstrap) {
		return 0;
	}

	/**
	 * Iterates over all existing sessions and removes their data if the inactivity
	 * timeout was reached.
	 *
	 * @return integer The number of outdated entries removed
	 * @api
	 */
	public function collectGarbage() {
		$decimals = strlen(strrchr($this->garbageCollectionProbability, '.')) -1;
		$factor = ($decimals > -1) ? $decimals * 10 : 1;
		if (rand(0, 100 * $factor) <= ($this->garbageCollectionProbability * $factor)) {
			$sessionRemovalCount = 0;
			if ($this->inactivityTimeout !== 0) {
				foreach ($this->cache->getByTag('session') as $sessionInfo) {
					$lastActivitySecondsAgo = time() - $sessionInfo['lastActivityTimestamp'];
					if ($lastActivitySecondsAgo > $this->inactivityTimeout) {
						$this->cache->flushByTag($sessionInfo['storageIdentifier']);
						$sessionRemovalCount ++;
					}
				}
			}
			return $sessionRemovalCount;
		} else {
			return FALSE;
		}
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
		if ($this->started === TRUE) {
			$this->putData('TYPO3_Flow_Object_ObjectManager', $this->objectManager->getSessionInstances());
			$sessionInfo = array(
				'lastActivityTimestamp' => time(),
				'storageIdentifier' => $this->storageIdentifier
			);
			$this->cache->set($this->sessionIdentifier, $sessionInfo, array($this->storageIdentifier, 'session'), 0);
			$this->started = FALSE;
		}
	}

	/**
	 * Automatically expires the session if the user has been inactive for too long.
	 *
	 * @return boolean TRUE if the session expired, FALSE if not
	 */
	protected function autoExpire() {
		$lastActivitySecondsAgo = time() - $this->lastActivityTimestamp;
		$expired = FALSE;
		if ($this->inactivityTimeout !== 0 && $lastActivitySecondsAgo > $this->inactivityTimeout) {
			$this->started = TRUE;
			$this->sessionIdentifier = $this->sessionCookie->getValue();
			$this->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $this->sessionIdentifier, $lastActivitySecondsAgo, $this->inactivityTimeout));
			$expired = TRUE;
		}

		$this->collectGarbage();
		return $expired;
	}
}

?>

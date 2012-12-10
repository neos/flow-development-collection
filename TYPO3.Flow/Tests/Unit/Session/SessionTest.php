<?php
namespace TYPO3\Flow\Tests\Unit\Session;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Session\Session;
use TYPO3\Flow\Session\SessionManager;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Cache\Backend\TransientMemoryBackend;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Cookie;
use TYPO3\Flow\Security\Authentication\Token\UsernamePassword;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Account;

/**
 * Unit tests for the Flow Session implementation
 */
class SessionTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $httpRequest;

	/**
	 * @var \TYPO3\Flow\Http\Response
	 */
	protected $httpResponse;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $mockSecurityContext;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $mockBootstrap;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var array
	 */
	protected $settings = array(
		'session' => array(
			'inactivityTimeout' => 3600,
			'name' => 'TYPO3_Flow_Session',
			'garbageCollectionProbability' => 1,
			'cookie' => array(
				'lifetime' => 0,
				'path' => '/',
				'secure' => FALSE,
				'httponly' => TRUE,
				'domain' => NULL
			)
		)
	);

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setup();

		$this->httpRequest = Request::create(new Uri('http://localhost'));
		$this->httpResponse = new Response();

		$mockRequestHandler = $this->getMock('TYPO3\Flow\Http\RequestHandler', array(), array(), '', FALSE, FALSE);
		$mockRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->httpRequest));
		$mockRequestHandler->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->httpResponse));

		$this->mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array(), array(), '', FALSE, FALSE);
		$this->mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

		$this->mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE, FALSE);

		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface', array(), array(), '', FALSE, FALSE);
		$this->mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Security\Context')->will($this->returnValue($this->mockSecurityContext));
	}

	/**
	 * @test
	 */
	public function constructCreatesARemoteSessionIfSessionIfIdentifierIsSpecified() {
		$storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
		$session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259);
		$this->assertTrue($session->isRemote());

		$session = new Session();
		$this->assertFalse($session->isRemote());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructRequiresAStorageIdentifierIfASessionIdentifierWasGiven() {
		new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
	}

	/**
	 * @test
	 */
	public function remoteSessionUsesStorageIdentifierPassedToConstructor() {
		$storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
		$session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, array());

		$cache = $this->createCache();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$this->assertFalse($session->hasKey('some key'));
		$session->putData('some key', 'some value');

		$this->assertEquals('some value', $session->getData('some key'));
		$this->assertTrue($session->hasKey('some key'));

		$this->assertTrue($cache->has($storageIdentifier . md5('some key')));
	}

	/**
	 * @test
	 */
	public function canBeResumedReturnsFalseIfNoSessionCookieExists() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->assertFalse($session->canBeResumed());
	}

	/**
	 * @test
	 */
	public function canBeResumedReturnsFalseIfTheSessionHasAlreadyBeenStarted() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->start();

		$this->assertFalse($session->canBeResumed());
	}

	/**
	 * @test
	 */
	public function canBeResumedReturnsFalseIfSessionIsExpiredAndExpiresASessionIfLastActivityIsOverTheLimit() {
		$cache = $this->createCache();

		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$session->start();
		$sessionIdentifier = $session->getId();
		$session->close();

		$this->assertTrue($session->canBeResumed());

		$sessionInfo = $cache->get($sessionIdentifier);
		$sessionInfo['lastActivityTimestamp'] = time() - 4000;
		$cache->set($sessionIdentifier, $sessionInfo, array($sessionInfo['storageIdentifier'], 'session'), 0);
		$this->assertFalse($session->canBeResumed());
	}

	/**
	 * @test
	 */
	public function isStartedReturnsFalseByDefault() {
		$session = new Session();
		$this->assertFalse($session->isStarted());
	}

	/**
	 * @test
	 */
	public function isStartedReturnsTrueAfterSessionHasBeenStarted() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->start();
		$this->assertTrue($session->isStarted());
	}

	/**
	 * @test
	 */
	public function resumeSetsSessionCookieInTheResponse() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();
		$sessionIdentifier = $session->getId();

		$session->close();

		$session->resume();

		$this->assertTrue($this->httpResponse->hasCookie('TYPO3_Flow_Session'));
		$this->assertEquals($sessionIdentifier, $this->httpResponse->getCookie('TYPO3_Flow_Session')->getValue());
	}

	/**
	 * @test
	 */
	public function resumeOnAStartedSessionDoesNotDoAnyHarm() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();

		$session->resume();
	}

	/**
	 * @test
	 */
	public function startPutsACookieIntoTheHttpResponse() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();

		$cookie = $this->httpResponse->getCookie('TYPO3_Flow_Session');
		$this->assertNotNull($cookie);
		$this->assertEquals($session->getId(), $cookie->getValue());
	}

	/**
	 * @test
	 */
	public function getIdReturnsTheCurrentSessionIdentifier() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		try {
			$session->getId();
			$this->fail('No exception thrown although the session was not started yet.');
		} catch (\TYPO3\Flow\Session\Exception\SessionNotStartedException $e) {
			$session->start();
			$this->assertEquals(32, strlen($session->getId()));
		}
	}

	/**
	 * @test
	 */
	public function renewIdSetsANewSessionIdentifier() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->start();

		$oldSessionId = $session->getId();
		$session->renewId();
		$newSessionId = $session->getId();
		$this->assertNotEquals($oldSessionId, $newSessionId);
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function renewIdThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->renewId();
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\OperationNotSupportedException
	 */
	public function renewIdThrowsExceptionIfCalledOnRemoteSession() {
		$storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
		$session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, array());
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());
		$session->renewId();
	}

	/**
	 * @test
	 */
	public function sessionDataCanBeRetrievedEvenAfterSessionIdHasBeenRenewed() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);

		$session->start();
		$session->putData('foo', 'bar');
		$session->renewId();
		$session->close();

		$sessionCookie = $this->httpResponse->getCookie($this->settings['session']['name']);
		$this->httpRequest->setCookie($sessionCookie);

		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$session->resume();

		$this->assertEquals('bar', $session->getData('foo'));
	}

	/**
	 * This test asserts that the session cookie sent in the response doesn't just
	 * copy the data from the received session cookie (that is, domain, httponly etc)
	 * but creates a fresh Cookie object using the parameters derived from the
	 * settings.
	 *
	 * @test
	 */
	public function sessionOnlyReusesTheSessionIdFromIncomingCookies() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);

		$session->start();
		$session->putData('foo', 'bar');
		$sessionIdentifier = $session->getId();
		$session->close();

		$requestCookie = new Cookie($this->settings['session']['name'], $sessionIdentifier, 0, 100, 'other', '/');
		$this->httpRequest->setCookie($requestCookie);

		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$session->resume();

		$responseCookie = $this->httpResponse->getCookie($this->settings['session']['name']);

		$this->assertNotEquals($requestCookie, $responseCookie);
		$this->assertEquals($requestCookie->getValue(), $responseCookie->getValue());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getDataThrowsExceptionIfSessionIsNotStarted() {
		$session = new Session();
		$session->getData('some key');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function putDataThrowsExceptionIfSessionIsNotStarted() {
		$session = new Session();
		$session->putData('some key', 'some value');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\DataNotSerializableException
	 */
	public function putDataThrowsExceptionIfTryingToPersistAResource() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();
		$resource = fopen(__FILE__, 'r');
		$session->putData('some key', $resource);
	}

	/**
	 * @test
	 */
	public function getDataReturnsDataPreviouslySetWithPutData() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();

		$this->assertFalse($session->hasKey('some key'));
		$session->putData('some key', 'some value');
		$this->assertEquals('some value', $session->getData('some key'));
		$this->assertTrue($session->hasKey('some key'));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function hasKeyThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$session->hasKey('foo');
	}

	/**
	 * @test
	 */
	public function twoSessionsDontConflictIfUsingSameEntryIdentifiers() {
		$cache = $this->createCache();

		$session1 = new Session();
		$this->inject($session1, 'bootstrap', $this->mockBootstrap);
		$this->inject($session1, 'settings', $this->settings);
		$this->inject($session1, 'cache', $cache);
		$session1->start();

		$session2 = new Session();
		$this->inject($session2, 'bootstrap', $this->mockBootstrap);
		$this->inject($session2, 'settings', $this->settings);
		$this->inject($session2, 'cache', $cache);
		$session2->start();

		$session1->putData('foo', 'bar');
		$session2->putData('foo', 'baz');

		$this->assertEquals('bar', $session1->getData('foo'));
		$this->assertEquals('baz', $session2->getData('foo'));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getLastActivityTimestampThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$session->getLastActivityTimestamp();
	}

	/**
	 * @test
	 */
	public function lastActivityTimestampOfNewSessionIsSetAndStoredCorrectlyAndCanBeRetrieved() {
		$session = $this->getAccessibleMock('TYPO3\Flow\Session\Session', array('dummy'));
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);

		$now = $session->_get('now');

		$session->start();
		$sessionIdentifier = $session->getId();
		$this->assertEquals($now, $session->getLastActivityTimestamp());

		$session->close();

		$sessionInfo = $cache->get($sessionIdentifier);
		$this->assertEquals($now, $sessionInfo['lastActivityTimestamp']);
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function addTagThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$session->addTag('MyTag');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addTagThrowsExceptionIfTagIsNotValid() {
		$taggedSession = new Session();
		$this->inject($taggedSession, 'bootstrap', $this->mockBootstrap);
		$this->inject($taggedSession, 'settings', $this->settings);
		$this->inject($taggedSession, 'cache', $this->createCache());
		$this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
		$taggedSession->start();

		$taggedSession->addTag('Invalid Tag Contains Spaces');
	}

	/**
	 * @test
	 */
	public function aSessionCanBeTaggedAndBeRetrievedAgainByTheseTags() {
		$cache = $this->createCache();

		$otherSession = new Session();
		$this->inject($otherSession, 'bootstrap', $this->mockBootstrap);
		$this->inject($otherSession, 'settings', $this->settings);
		$this->inject($otherSession, 'cache', $cache);
		$this->inject($otherSession, 'objectManager', $this->mockObjectManager);
		$otherSession->start();

		$taggedSession = new Session();
		$this->inject($taggedSession, 'bootstrap', $this->mockBootstrap);
		$this->inject($taggedSession, 'settings', $this->settings);
		$this->inject($taggedSession, 'cache', $cache);
		$this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
		$taggedSession->start();
		$taggedSessionId = $taggedSession->getId();

		$otherSession->putData('foo', 'bar');
		$taggedSession->putData('foo', 'baz');

		$taggedSession->addTag('SampleTag');
		$taggedSession->addTag('AnotherTag');

		$otherSession->close();
		$taggedSession->close();

		$sessionManager = new SessionManager();
		$this->inject($sessionManager, 'cache', $cache);

		$retrievedSessions = $sessionManager->getSessionsByTag('SampleTag');
		$this->assertSame($taggedSessionId, $retrievedSessions[0]->getId());
		$this->assertEquals(array('SampleTag', 'AnotherTag'), $retrievedSessions[0]->getTags());
	}

	/**
	 * @test
	 */
	public function getTagsOnAResumedSessionReturnsTheTagsSetWithAddTag() {
		$cache = $this->createCache();

		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$session->start();
		$session->addTag('SampleTag');
		$session->addTag('AnotherTag');

		$session->close();

			// Create a new, clean session object to make sure that the tags were really
			// loaded from the cache:
		$sessionCookie = $this->httpResponse->getCookie($this->settings['session']['name']);
		$this->httpRequest->setCookie($sessionCookie);

		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$session->resume();

		$this->assertEquals(array('SampleTag', 'AnotherTag'), $session->getTags());
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getTagsThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$session->getTags();
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function removeTagThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$session->removeTag('MyTag');
	}

	/**
	 * @test
	 */
	public function removeTagRemovesAPreviouslySetTag() {
		$taggedSession = new Session();
		$this->inject($taggedSession, 'bootstrap', $this->mockBootstrap);
		$this->inject($taggedSession, 'settings', $this->settings);
		$this->inject($taggedSession, 'cache', $this->createCache());
		$this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
		$taggedSession->start();

		$taggedSession->addTag('SampleTag');
		$taggedSession->addTag('AnotherTag');

		$taggedSession->removeTag('SampleTag');
		$taggedSession->addTag('YetAnotherTag');

		$taggedSession->removeTag('DoesntExistButDoesNotAnyHarm');

		$this->assertEquals(array('AnotherTag', 'YetAnotherTag'), array_values($taggedSession->getTags()));
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function touchThrowsExceptionIfCalledOnNonStartedSession() {
		$session = new Session();
		$session->touch();
	}

	/**
	 * @test
	 */
	public function touchUpdatesLastActivityTimestampOfRemoteSession() {
		$storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
		$cache = $this->createCache();
		$session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1110000000);
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $cache);

		$this->inject($session, 'now', 2220000000);

		$session->touch();

		$sessionInfo = $cache->get('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
		$this->assertEquals(2220000000, $sessionInfo['lastActivityTimestamp']);
		$this->assertEquals($storageIdentifier, $sessionInfo['storageIdentifier']);
	}

	/**
	 * @test
	 */
	public function closeFlagsTheSessionAsClosed() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();
		$this->assertTrue($session->isStarted());

		$session->close();
		$this->assertFalse($session->isStarted());
	}

	/**
	 * @test
	 */
	public function closeAndShutdownObjectDoNotCloseARemoteSession() {
		$storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
		$session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, array());
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();
		$this->assertTrue($session->isStarted());

		$session->close();
		$this->assertTrue($session->isStarted());
	}

	/**
	 * @test
	 */
	public function shutdownCreatesSpecialDataEntryForSessionWithAuthenticatedAccounts() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$this->inject($session, 'cache', $this->createCache());

		$session->start();

		$account = new Account();
		$account->setAccountIdentifier('admin');
		$account->setAuthenticationProviderName('MyProvider');

		$token = new UsernamePassword();
		$token->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$token->setAccount($account);

		$this->mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token)));

		$session->close();

		$this->httpRequest->setCookie($this->httpResponse->getCookie('TYPO3_Flow_Session'));

		$session->resume();
		$this->assertEquals(array('MyProvider:admin'), $session->getData('TYPO3_Flow_Security_Accounts'));
	}

	/**
	 * @test
	 */
	public function shutdownChecksIfSessionStillExistsInStorageCacheBeforeWritingData() {
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);

			// Start a "local" session and store some data:
		$session->start();
		$sessionIdentifier = $session->getId();

		$session->putData('foo', 'bar');
		$session->close();
		$sessionInfo = $cache->get($sessionIdentifier);

			// Simulate a remote server referring to the same session:
		$remoteSession = new Session($sessionIdentifier, $sessionInfo['storageIdentifier'], $sessionInfo['lastActivityTimestamp']);
		$this->inject($remoteSession, 'bootstrap', $this->mockBootstrap);
		$this->inject($remoteSession, 'objectManager', $this->mockObjectManager);
		$this->inject($remoteSession, 'settings', $this->settings);
		$this->inject($remoteSession, 'cache', $cache);

			// Resume the local session and add more data:
		$this->assertTrue($cache->has($sessionIdentifier));
		$session->resume();
		$session->putData('baz', 'quux');

			// The remote server destroys the local session in the meantime:
		$remoteSession->destroy();

			// Close the local session – this must not write any data because the session doesn't exist anymore:
		$session->close();

		$this->assertFalse($cache->has($sessionIdentifier));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function destroyThrowsExceptionIfSessionIsNotStarted() {
		$session = new Session();
		$session->destroy();
	}

	/**
	 * @test
	 */
	public function destroyRemovesAllSessionDataFromTheCurrentSessionButNotFromOtherSessions() {
		$session1 = new Session();
		$session2 = new Session();

		$this->inject($session1, 'bootstrap', $this->mockBootstrap);
		$this->inject($session2, 'bootstrap', $this->mockBootstrap);
		$this->inject($session1, 'settings', $this->settings);
		$this->inject($session2, 'settings', $this->settings);

		$cache = $this->createCache();
		$this->inject($session1, 'cache', $cache);
		$this->inject($session2, 'cache', $cache);

		$session1->start();
		$session2->start();

		$session1->putData('session 1 key 1', 'some value');
		$session1->putData('session 1 key 2', 'some other value');
		$session2->putData('session 2 key', 'some value');

		$session1->destroy(__METHOD__);

		$this->inject($session1, 'started', TRUE);
		$this->inject($session2, 'started', TRUE);
		$this->assertFalse($session1->hasKey('session 1 key 1'));
		$this->assertFalse($session1->hasKey('session 1 key 2'));
		$this->assertTrue($session2->hasKey('session 2 key'), 'Entry in session was also removed.');
	}

	/**
	 * @test
	 */
	public function destroyRemovesAllSessionDataFromARemoteSession() {
		$storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
		$session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, array());

		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'settings', $this->settings);

		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);

		$session->start();

		$session->putData('session 1 key 1', 'some value');
		$session->putData('session 1 key 2', 'some other value');

		$session->destroy(__METHOD__);

		$this->inject($session, 'started', TRUE);
		$this->assertFalse($session->hasKey('session 1 key 1'));
		$this->assertFalse($session->hasKey('session 1 key 2'));
	}

	/**
	 * @test
	 */
	public function autoExpireRemovesAllSessionDataOfTheExpiredSession() {
		$session = $this->getAccessibleMock('TYPO3\Flow\Session\Session', array('dummy'));
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'settings', $this->settings);
		$cache = $this->createCache();
		$this->inject($session, 'cache', $cache);

		$session->start();
		$sessionIdentifier = $session->getId();
		$storageIdentifier = $session->_get('storageIdentifier');

		$session->putData('session 1 key 1', 'some value');
		$session->putData('session 1 key 2', 'some other value');

		$this->assertTrue($cache->has($storageIdentifier . md5('session 1 key 1')));
		$this->assertTrue($cache->has($storageIdentifier . md5('session 1 key 2')));

		$session->close();

		$sessionInfo = $cache->get($sessionIdentifier);
		$sessionInfo['lastActivityTimestamp'] = time() - 4000;
		$cache->set($sessionIdentifier, $sessionInfo, array($storageIdentifier, 'session'), 0);

			// canBeResumed implicitly calls autoExpire():
		$this->assertFalse($session->canBeResumed(), 'canBeResumed');

		$this->assertFalse($cache->has($storageIdentifier . md5('session 1 key 1')));
		$this->assertFalse($cache->has($storageIdentifier . md5('session 1 key 2')));
	}

	/**
	 * @test
	 */
	public function autoExpireTriggersGarbageCollectionForExpiredSessions() {
		$settings = $this->settings;
		$settings['session']['inactivityTimeout'] = 5000;
		$settings['session']['garbageCollectionProbability'] = 100;

		$cache = $this->createCache();

			// Create a session which first runs fine and then expires by later modifying
			// the inactivity timeout:
		$session = new Session();
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'cache', $cache);
		$session->injectSettings($settings);

		$session->start();
		$sessionIdentifier1 = $session->getId();
		$session->putData('session 1 key 1', 'session 1 value 1');
		$session->putData('session 1 key 2', 'session 1 value 2');
		$session->close();

		$session->resume();
		$this->assertTrue($session->isStarted());
		$session->close();

		$sessionInfo1 = $cache->get($sessionIdentifier1);
		$sessionInfo1['lastActivityTimestamp'] = time() - 4000;
		$cache->set($sessionIdentifier1, $sessionInfo1, array($sessionInfo1['storageIdentifier'], 'session'), 0);

			// Because we change the timeout post factum, the previously valid session
			// now expires:
		$settings['session']['inactivityTimeout'] = 3000;

			// Create a second session which should remove the first expired session
			// implicitly by calling autoExpire()
		$session = $this->getAccessibleMock('TYPO3\Flow\Session\Session', array('dummy'));
		$this->inject($session, 'bootstrap', $this->mockBootstrap);
		$this->inject($session, 'objectManager', $this->mockObjectManager);
		$this->inject($session, 'cache', $cache);
		$session->injectSettings($settings);

		$session->start();
		$sessionIdentifier2 = $session->getId();
		$session->putData('session 2 key 1', 'session 1 value 1');
		$session->putData('session 2 key 2', 'session 1 value 2');
		$session->close();

			// Calls autoExpire() internally:
		$session->resume();

		$sessionInfo2 = $cache->get($sessionIdentifier2);

			// Check how the cache looks like - data of session 1 should be gone:
		$this->assertFalse($cache->has($sessionInfo1['storageIdentifier'] . md5('session 1 key 1')), 'session 1 key 1 still there');
		$this->assertFalse($cache->has($sessionInfo1['storageIdentifier'] . md5('session 1 key 2')), 'session 1 key 2 still there');
		$this->assertTrue($cache->has($sessionInfo2['storageIdentifier'] . md5('session 2 key 1')), 'session 2 key 1 not there');
		$this->assertTrue($cache->has($sessionInfo2['storageIdentifier'] . md5('session 2 key 2')), 'session 2 key 2 not there');
	}

	/**
	 * Creates a cache for testing
	 */
	protected function createCache() {
		$backend = new TransientMemoryBackend(new ApplicationContext('Testing'), array());
		$cache = new VariableFrontend('SessionTest', $backend);
		return $cache;
	}
}
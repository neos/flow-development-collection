<?php
namespace Neos\Flow\Tests\Unit\Session;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\Session;
use Neos\Flow\Session\SessionManager;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Unit tests for the Flow Session implementation
 */
class SessionTest extends UnitTestCase
{
    /**
     * @var Http\Request
     */
    protected $httpRequest;

    /**
     * @var Http\Response
     */
    protected $httpResponse;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var Bootstrap
     */
    protected $mockBootstrap;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var array
     */
    protected $settings = [
        'session' => [
            'inactivityTimeout' => 3600,
            'name' => 'Neos_Flow_Session',
            'garbageCollection' => [
                'probability' => 1,
                'maximumPerRun' => 1000,
            ],
            'cookie' => [
                'lifetime' => 0,
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'domain' => null
            ]
        ]
    ];

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setup();

        vfsStream::setup('Foo');

        $this->httpRequest = Http\Request::create(new Http\Uri('http://localhost'));
        $this->httpResponse = new Http\Response();

        $mockRequestHandler = $this->createMock(Http\RequestHandler::class);
        $mockRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->httpRequest));
        $mockRequestHandler->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->httpResponse));

        $this->mockBootstrap = $this->createMock(Bootstrap::class);
        $this->mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

        $this->mockSecurityContext = $this->createMock(Context::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects($this->any())->method('get')->with(Context::class)->will($this->returnValue($this->mockSecurityContext));
    }

    /**
     * @test
     */
    public function constructCreatesARemoteSessionIfSessionIfIdentifierIsSpecified()
    {
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
    public function constructRequiresAStorageIdentifierIfASessionIdentifierWasGiven()
    {
        new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
    }

    /**
     * @test
     */
    public function remoteSessionUsesStorageIdentifierPassedToConstructor()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $this->assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');

        $this->assertEquals('some value', $session->getData('some key'));
        $this->assertTrue($session->hasKey('some key'));

        $this->assertTrue($storageCache->has($storageIdentifier . md5('some key')));
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfNoSessionCookieExists()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfTheSessionHasAlreadyBeenStarted()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $this->assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfSessionIsExpiredAndExpiresASessionIfLastActivityIsOverTheLimit()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->start();
        $sessionIdentifier = $session->getId();
        $session->close();

        $this->assertTrue($session->canBeResumed());

        $sessionInfo = $metaDataCache->get($sessionIdentifier);
        $sessionInfo['lastActivityTimestamp'] = time() - 4000;
        $metaDataCache->set($sessionIdentifier, $sessionInfo, [$sessionInfo['storageIdentifier'], 'session'], 0);
        $this->assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function isStartedReturnsFalseByDefault()
    {
        $session = new Session();
        $this->assertFalse($session->isStarted());
    }

    /**
     * @test
     */
    public function isStartedReturnsTrueAfterSessionHasBeenStarted()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
        $session->start();
        $this->assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function resumeSetsSessionCookieInTheResponse()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();
        $sessionIdentifier = $session->getId();

        $session->close();

        $session->resume();

        $this->assertTrue($this->httpResponse->hasCookie('Neos_Flow_Session'));
        $this->assertEquals($sessionIdentifier, $this->httpResponse->getCookie('Neos_Flow_Session')->getValue());
    }

    /**
     * Assures that no exception is thrown if a session is resumed.
     *
     * @test
     */
    public function resumeOnAStartedSessionDoesNotDoAnyHarm()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $session->resume();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function startPutsACookieIntoTheHttpResponse()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $cookie = $this->httpResponse->getCookie('Neos_Flow_Session');
        $this->assertNotNull($cookie);
        $this->assertEquals($session->getId(), $cookie->getValue());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\InvalidRequestHandlerException
     */
    public function startThrowsAnExceptionIfIncompatibleRequestHandlerIsUsed()
    {
        $mockBootstrap = $this->createMock(Bootstrap::class);
        $mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue(new \stdClass()));

        $session = new Session();
        $this->inject($session, 'bootstrap', $mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();
    }

    /**
     * @test
     */
    public function getIdReturnsTheCurrentSessionIdentifier()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        try {
            $session->getId();
            $this->fail('No exception thrown although the session was not started yet.');
        } catch (SessionNotStartedException $e) {
            $session->start();
            $this->assertEquals(32, strlen($session->getId()));
        }
    }

    /**
     * @test
     */
    public function renewIdSetsANewSessionIdentifier()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
        $session->start();

        $oldSessionId = $session->getId();
        $session->renewId();
        $newSessionId = $session->getId();
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function renewIdThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
        $session->renewId();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\OperationNotSupportedException
     */
    public function renewIdThrowsExceptionIfCalledOnRemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
        $session->renewId();
    }

    /**
     * @test
     */
    public function sessionDataCanBeRetrievedEvenAfterSessionIdHasBeenRenewed()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

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
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

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
    public function sessionOnlyReusesTheSessionIdFromIncomingCookies()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->start();
        $session->putData('foo', 'bar');
        $sessionIdentifier = $session->getId();
        $session->close();

        $requestCookie = new Http\Cookie($this->settings['session']['name'], $sessionIdentifier, 0, 100, 'other', '/');
        $this->httpRequest->setCookie($requestCookie);

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->resume();

        $responseCookie = $this->httpResponse->getCookie($this->settings['session']['name']);

        $this->assertNotEquals($requestCookie, $responseCookie);
        $this->assertEquals($requestCookie->getValue(), $responseCookie->getValue());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function getDataThrowsExceptionIfSessionIsNotStarted()
    {
        $session = new Session();
        $session->getData('some key');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function putDataThrowsExceptionIfSessionIsNotStarted()
    {
        $session = new Session();
        $session->putData('some key', 'some value');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\DataNotSerializableException
     */
    public function putDataThrowsExceptionIfTryingToPersistAResource()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();
        $resource = fopen(__FILE__, 'r');
        $session->putData('some key', $resource);
    }

    /**
     * @test
     */
    public function getDataReturnsDataPreviouslySetWithPutData()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $this->assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');
        $this->assertEquals('some value', $session->getData('some key'));
        $this->assertTrue($session->hasKey('some key'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function hasKeyThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $session->hasKey('foo');
    }

    /**
     * @test
     */
    public function twoSessionsDontConflictIfUsingSameEntryIdentifiers()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session1 = new Session();
        $this->inject($session1, 'bootstrap', $this->mockBootstrap);
        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session1, 'metaDataCache', $metaDataCache);
        $this->inject($session1, 'storageCache', $storageCache);
        $session1->initializeObject();
        $session1->start();

        $session2 = new Session();
        $this->inject($session2, 'bootstrap', $this->mockBootstrap);
        $this->inject($session2, 'settings', $this->settings);
        $this->inject($session2, 'metaDataCache', $metaDataCache);
        $this->inject($session2, 'storageCache', $storageCache);
        $session2->initializeObject();
        $session2->start();

        $session1->putData('foo', 'bar');
        $session2->putData('foo', 'baz');

        $this->assertEquals('bar', $session1->getData('foo'));
        $this->assertEquals('baz', $session2->getData('foo'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function getLastActivityTimestampThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $session->getLastActivityTimestamp();
    }

    /**
     * @test
     */
    public function lastActivityTimestampOfNewSessionIsSetAndStoredCorrectlyAndCanBeRetrieved()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        /** @var Session $session */
        $session = $this->getAccessibleMock(Session::class, array('dummy'));
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $now = $session->_get('now');

        $session->start();
        $sessionIdentifier = $session->getId();
        $this->assertEquals($now, $session->getLastActivityTimestamp());

        $session->close();

        $sessionInfo = $metaDataCache->get($sessionIdentifier);
        $this->assertEquals($now, $sessionInfo['lastActivityTimestamp']);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function addTagThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $session->addTag('MyTag');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addTagThrowsExceptionIfTagIsNotValid()
    {
        $taggedSession = new Session();
        $this->inject($taggedSession, 'bootstrap', $this->mockBootstrap);
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($taggedSession, 'storageCache', $this->createCache('Storage'));
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->initializeObject();
        $taggedSession->start();

        $taggedSession->addTag('Invalid Tag Contains Spaces');
    }

    /**
     * @test
     */
    public function aSessionCanBeTaggedAndBeRetrievedAgainByTheseTags()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $otherSession = new Session();
        $this->inject($otherSession, 'bootstrap', $this->mockBootstrap);
        $this->inject($otherSession, 'settings', $this->settings);
        $this->inject($otherSession, 'metaDataCache', $metaDataCache);
        $this->inject($otherSession, 'storageCache', $storageCache);
        $this->inject($otherSession, 'objectManager', $this->mockObjectManager);
        $otherSession->initializeObject();
        $otherSession->start();

        $taggedSession = new Session();
        $this->inject($taggedSession, 'bootstrap', $this->mockBootstrap);
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'metaDataCache', $metaDataCache);
        $this->inject($taggedSession, 'storageCache', $storageCache);
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->initializeObject();
        $taggedSession->start();
        $taggedSessionId = $taggedSession->getId();

        $otherSession->putData('foo', 'bar');
        $taggedSession->putData('foo', 'baz');

        $taggedSession->addTag('SampleTag');
        $taggedSession->addTag('AnotherTag');

        $otherSession->close();
        $taggedSession->close();

        $sessionManager = new SessionManager();
        $this->inject($sessionManager, 'metaDataCache', $metaDataCache);

        $retrievedSessions = $sessionManager->getSessionsByTag('SampleTag');
        $this->assertSame($taggedSessionId, $retrievedSessions[0]->getId());
        $this->assertEquals(['SampleTag', 'AnotherTag'], $retrievedSessions[0]->getTags());
    }

    /**
     * @test
     */
    public function getActiveSessionsReturnsAllActiveSessions()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $sessions = [];
        $sessionIDs = [];
        for ($i = 0; $i < 5; $i++) {
            $session = new Session();
            $this->inject($session, 'bootstrap', $this->mockBootstrap);
            $this->inject($session, 'settings', $this->settings);
            $this->inject($session, 'metaDataCache', $metaDataCache);
            $this->inject($session, 'storageCache', $storageCache);
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $session->initializeObject();
            $session->start();
            $sessions[] = $session;
            $sessionIDs[] = $session->getId();
            $session->close();
        }

        $sessionManager = new SessionManager();
        $this->inject($sessionManager, 'metaDataCache', $metaDataCache);

        $activeSessions = $sessionManager->getActiveSessions();

        $this->assertCount(5, $activeSessions);

        /* @var $randomActiveSession Session */
        $randomActiveSession = $activeSessions[array_rand($activeSessions)];
        $randomActiveSession->resume();

        $this->assertContains($randomActiveSession->getId(), $sessionIDs);
    }

    /**
     * @test
     */
    public function getTagsOnAResumedSessionReturnsTheTagsSetWithAddTag()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

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
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();
        $this->assertNotNull($session->resume(), 'The session was not properly resumed.');

        $this->assertEquals(['SampleTag', 'AnotherTag'], $session->getTags());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function getTagsThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $session->getTags();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function removeTagThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $session->removeTag('MyTag');
    }

    /**
     * @test
     */
    public function removeTagRemovesAPreviouslySetTag()
    {
        $taggedSession = new Session();
        $this->inject($taggedSession, 'bootstrap', $this->mockBootstrap);
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($taggedSession, 'storageCache', $this->createCache('Storage'));
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->initializeObject();
        $taggedSession->start();

        $taggedSession->addTag('SampleTag');
        $taggedSession->addTag('AnotherTag');

        $taggedSession->removeTag('SampleTag');
        $taggedSession->addTag('YetAnotherTag');

        $taggedSession->removeTag('DoesntExistButDoesNotAnyHarm');

        $this->assertEquals(['AnotherTag', 'YetAnotherTag'], array_values($taggedSession->getTags()));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function touchThrowsExceptionIfCalledOnNonStartedSession()
    {
        $session = new Session();
        $session->touch();
    }

    /**
     * @test
     */
    public function touchUpdatesLastActivityTimestampOfRemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $metaDataCache = $this->createCache('Meta');

        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1110000000);
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $this->inject($session, 'now', 2220000000);

        $session->touch();

        $sessionInfo = $metaDataCache->get('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        $this->assertEquals(2220000000, $sessionInfo['lastActivityTimestamp']);
        $this->assertEquals($storageIdentifier, $sessionInfo['storageIdentifier']);
    }

    /**
     * @test
     */
    public function closeFlagsTheSessionAsClosed()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();
        $this->assertTrue($session->isStarted());

        $session->close();
        $this->assertFalse($session->isStarted());
    }

    /**
     * @test
     */
    public function closeAndShutdownObjectDoNotCloseARemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();
        $this->assertTrue($session->isStarted());

        $session->close();
        $this->assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function shutdownCreatesSpecialDataEntryForSessionWithAuthenticatedAccounts()
    {
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $account = new Account();
        $account->setAccountIdentifier('admin');
        $account->setAuthenticationProviderName('MyProvider');

        $token = new UsernamePassword();
        $token->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
        $token->setAccount($account);

        $this->mockSecurityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));
        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue([$token]));

        $session->close();

        $this->httpRequest->setCookie($this->httpResponse->getCookie('Neos_Flow_Session'));

        $session->resume();
        $this->assertEquals(['MyProvider:admin'], $session->getData('Neos_Flow_Security_Accounts'));
    }

    /**
     * @test
     */
    public function shutdownChecksIfSessionStillExistsInStorageCacheBeforeWritingData()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        // Start a "local" session and store some data:
        $session->start();
        $sessionIdentifier = $session->getId();

        $session->putData('foo', 'bar');
        $session->close();
        $sessionInfo = $metaDataCache->get($sessionIdentifier);

        // Simulate a remote server referring to the same session:
        $remoteSession = new Session($sessionIdentifier, $sessionInfo['storageIdentifier'], $sessionInfo['lastActivityTimestamp']);
        $this->inject($remoteSession, 'bootstrap', $this->mockBootstrap);
        $this->inject($remoteSession, 'objectManager', $this->mockObjectManager);
        $this->inject($remoteSession, 'settings', $this->settings);
        $this->inject($remoteSession, 'metaDataCache', $metaDataCache);
        $this->inject($remoteSession, 'storageCache', $storageCache);
        $remoteSession->initializeObject();

        // Resume the local session and add more data:
        $this->assertTrue($metaDataCache->has($sessionIdentifier));
        $session->resume();
        $session->putData('baz', 'quux');

        // The remote server destroys the local session in the meantime:
        $remoteSession->destroy();

        // Close the local session – this must not write any data because the session doesn't exist anymore:
        $session->close();

        $this->assertFalse($metaDataCache->has($sessionIdentifier));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function destroyThrowsExceptionIfSessionIsNotStarted()
    {
        $session = new Session();
        $session->destroy();
    }

    /**
     * @test
     */
    public function destroyRemovesAllSessionDataFromTheCurrentSessionButNotFromOtherSessions()
    {
        $session1 = new Session();
        $session2 = new Session();

        $this->inject($session1, 'bootstrap', $this->mockBootstrap);
        $this->inject($session2, 'bootstrap', $this->mockBootstrap);
        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session2, 'settings', $this->settings);

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');
        $this->inject($session1, 'metaDataCache', $metaDataCache);
        $this->inject($session1, 'storageCache', $storageCache);
        $this->inject($session2, 'metaDataCache', $metaDataCache);
        $this->inject($session2, 'storageCache', $storageCache);
        $session1->initializeObject();
        $session2->initializeObject();

        $session1->start();
        $session2->start();

        $session1->putData('session 1 key 1', 'some value');
        $session1->putData('session 1 key 2', 'some other value');
        $session2->putData('session 2 key', 'some value');

        $session1->destroy(__METHOD__);

        $this->inject($session1, 'started', true);
        $this->inject($session2, 'started', true);
        $this->assertFalse($session1->hasKey('session 1 key 1'));
        $this->assertFalse($session1->hasKey('session 1 key 2'));
        $this->assertTrue($session2->hasKey('session 2 key'), 'Entry in session was also removed.');
    }

    /**
     * @test
     */
    public function destroyRemovesAllSessionDataFromARemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        $session->destroy(__METHOD__);

        $this->inject($session, 'started', true);
        $this->assertFalse($session->hasKey('session 1 key 1'));
        $this->assertFalse($session->hasKey('session 1 key 2'));
    }

    /**
     * @test
     */
    public function autoExpireRemovesAllSessionDataOfTheExpiredSession()
    {
        /** @var Session $session */
        $session = $this->getAccessibleMock(Session::class, ['dummy']);
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);

        $session->initializeObject();

        $session->start();
        $sessionIdentifier = $session->getId();
        $storageIdentifier = $session->_get('storageIdentifier');

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        $this->assertTrue($storageCache->has($storageIdentifier . md5('session 1 key 1')));
        $this->assertTrue($storageCache->has($storageIdentifier . md5('session 1 key 2')));

        $session->close();

        $sessionInfo = $metaDataCache->get($sessionIdentifier);
        $sessionInfo['lastActivityTimestamp'] = time() - 4000;
        $metaDataCache->set($sessionIdentifier, $sessionInfo, [$storageIdentifier, 'session'], 0);

        // canBeResumed implicitly calls autoExpire():
        $this->assertFalse($session->canBeResumed(), 'canBeResumed');

        $this->assertFalse($storageCache->has($storageIdentifier . md5('session 1 key 1')));
        $this->assertFalse($storageCache->has($storageIdentifier . md5('session 1 key 2')));
    }

    /**
     * @test
     */
    public function autoExpireTriggersGarbageCollectionForExpiredSessions()
    {
        $settings = $this->settings;
        $settings['session']['inactivityTimeout'] = 5000;
        $settings['session']['garbageCollection']['probability'] = 100;

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        // Create a session which first runs fine and then expires by later modifying
        // the inactivity timeout:
        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->injectSettings($settings);
        $session->initializeObject();

        $session->start();
        $sessionIdentifier1 = $session->getId();
        $session->putData('session 1 key 1', 'session 1 value 1');
        $session->putData('session 1 key 2', 'session 1 value 2');
        $session->close();

        $session->resume();
        $this->assertTrue($session->isStarted());
        $this->assertTrue($metaDataCache->has($sessionIdentifier1), 'session 1 meta entry doesnt exist');
        $session->close();

        $sessionInfo1 = $metaDataCache->get($sessionIdentifier1);
        $sessionInfo1['lastActivityTimestamp'] = time() - 4000;
        $metaDataCache->set($sessionIdentifier1, $sessionInfo1, ['session'], 0);

        // Because we change the timeout post factum, the previously valid session
        // now expires:
        $settings['session']['inactivityTimeout'] = 3000;

        // Create a second session which should remove the first expired session
        // implicitly by calling autoExpire()
        /** @var Session $session */
        $session = $this->getAccessibleMock(Session::class, ['dummy']);
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->injectSettings($settings);
        $session->initializeObject();

        $session->start();
        $sessionIdentifier2 = $session->getId();
        $session->putData('session 2 key 1', 'session 1 value 1');
        $session->putData('session 2 key 2', 'session 1 value 2');
        $session->close();

        // Calls autoExpire() internally:
        $session->resume();

        $sessionInfo2 = $metaDataCache->get($sessionIdentifier2);

        // Check how the cache looks like - data of session 1 should be gone:
        $this->assertFalse($metaDataCache->has($sessionIdentifier1), 'session 1 meta entry still there');
        $this->assertFalse($storageCache->has($sessionInfo1['storageIdentifier'] . md5('session 1 key 1')), 'session 1 key 1 still there');
        $this->assertFalse($storageCache->has($sessionInfo1['storageIdentifier'] . md5('session 1 key 2')), 'session 1 key 2 still there');
        $this->assertTrue($storageCache->has($sessionInfo2['storageIdentifier'] . md5('session 2 key 1')), 'session 2 key 1 not there');
        $this->assertTrue($storageCache->has($sessionInfo2['storageIdentifier'] . md5('session 2 key 2')), 'session 2 key 2 not there');
    }

    /**
     * @test
     */
    public function garbageCollectionIsOmittedIfInactivityTimeoutIsSetToZero()
    {
        $settings = $this->settings;
        $settings['session']['inactivityTimeout'] = 0;

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->injectSettings($settings);
        $session->initializeObject();

        $this->assertSame(0, $session->collectGarbage());
    }

    /**
     * @test
     */
    public function garbageCollectionIsOmittedIfAnotherProcessIsAlreadyRunning()
    {
        $settings = $this->settings;
        $settings['session']['inactivityTimeout'] = 5000;
        $settings['session']['garbageCollection']['probability'] = 100;

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'bootstrap', $this->mockBootstrap);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->injectSettings($settings);
        $session->initializeObject();

        // No sessions need to be removed:
        $this->assertSame(0, $session->collectGarbage());

        $metaDataCache->set('_garbage-collection-running', true, [], 120);

        // Session garbage collection is omitted:
        $this->assertFalse($session->collectGarbage());
    }

    /**
     * @test
     */
    public function garbageCollectionOnlyRemovesTheDefinedMaximumNumberOfSessions()
    {
        $settings = $this->settings;
        $settings['session']['inactivityTimeout'] = 1000;
        $settings['session']['garbageCollection']['probability'] = 0;
        $settings['session']['garbageCollection']['maximumPerRun'] = 5;

        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        for ($i = 0; $i < 9; $i++) {
            $session = new Session();
            $this->inject($session, 'bootstrap', $this->mockBootstrap);
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $this->inject($session, 'metaDataCache', $metaDataCache);
            $this->inject($session, 'storageCache', $storageCache);
            $session->injectSettings($settings);
            $this->inject($session, 'systemLogger', $this->createMock(SystemLoggerInterface::class));
            $session->initializeObject();

            $session->start();
            $sessionIdentifier = $session->getId();
            $session->putData('foo', 'bar');
            $session->close();

            $sessionInfo = $metaDataCache->get($sessionIdentifier);
            $sessionInfo['lastActivityTimestamp'] = time() - 4000;
            $metaDataCache->set($sessionIdentifier, $sessionInfo, ['session'], 0);
        }

        $this->assertLessThanOrEqual(5, $session->collectGarbage());
    }

    /**
     * Creates a cache for testing
     *
     * @param string $name
     * @return VariableFrontend
     */
    protected function createCache($name)
    {
        $backend = new FileBackend(new EnvironmentConfiguration('Session Testing', 'vfs://Foo/', PHP_MAXPATHLEN));
        $cache = new VariableFrontend($name, $backend);
        $cache->initializeObject();
        $backend->setCache($cache);
        $cache->flush();
        return $cache;
    }
}

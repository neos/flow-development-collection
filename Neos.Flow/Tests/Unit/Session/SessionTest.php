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

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Session\Data\SessionDataStore;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use Neos\Flow\Session\Exception\DataNotSerializableException;
use Neos\Flow\Session\Exception\OperationNotSupportedException;
use org\bovigo\vfs\vfsStream;
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\Session;
use Neos\Flow\Session\SessionManager;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unit tests for the Flow Session implementation
 */
class SessionTest extends UnitTestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest;

    /**
     * @var ResponseInterface
     */
    protected $httpResponse;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
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
                'domain' => null,
                'samesite' => Cookie::SAMESITE_LAX
            ]
        ]
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setup();

        vfsStream::setup('Foo');

        $serverRequestFactory = new ServerRequestFactory(new UriFactory());
        $this->httpRequest = $serverRequestFactory->createServerRequest('GET', new Uri('http://localhost'));
        $this->httpResponse = new Response();

        $mockRequestHandler = $this->createMock(RequestHandler::class);
        $mockRequestHandler->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->httpRequest));
        $mockRequestHandler->expects(self::any())->method('getHttpResponse')->will(self::returnValue($this->httpResponse));

        $this->mockBootstrap = $this->createMock(Bootstrap::class);
        $this->mockBootstrap->expects(self::any())->method('getActiveRequestHandler')->will(self::returnValue($mockRequestHandler));

        $this->mockSecurityContext = $this->createMock(Context::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('get')->with(Context::class)->will(self::returnValue($this->mockSecurityContext));
    }

    /**
     * @test
     */
    public function constructCreatesARemoteSessionIfSessionIfIdentifierIsSpecified()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        self::assertTrue($session->isRemote());

        $session = Session::create();
        self::assertFalse($session->isRemote());
    }

    /**
     * @test
     */
    public function remoteSessionUsesStorageIdentifierPassedToConstructor()
    {
        $sessionIdentifier = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $metadata = new SessionMetaData(
            $sessionIdentifier,
            $storageIdentifier,
            1354293259,
            []
        );
        $session = Session::createRemoteFromSessionMetaData($metadata);

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        self::assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');

        self::assertEquals('some value', $session->getData('some key'));
        self::assertTrue($session->hasKey('some key'));

        self::assertTrue($sessionDataStore->has($metadata, 'some key'));
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfNoSessionCookieExists()
    {
        $session = Session::create();
        self::assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfTheSessionHasAlreadyBeenStarted()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();

        self::assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfSessionIsExpiredAndExpiresASessionIfLastActivityIsOverTheLimit()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $session->start();
        $sessionIdentifier = $session->getId();
        $session->close();

        self::assertTrue($session->canBeResumed());

        $sessionInfo = $sessionMetaDataStore->findBySessionIdentifier($sessionIdentifier);
        $sessionInfo = $sessionInfo->withLastActivityTimestamp(time() - 4000);
        $sessionMetaDataStore->store($sessionInfo);
        self::assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function isStartedReturnsFalseByDefault()
    {
        $session = Session::create();
        self::assertFalse($session->isStarted());
    }

    /**
     * @test
     */
    public function isStartedReturnsTrueAfterSessionHasBeenStarted()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();
        self::assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function resumeSetsSessionCookieInTheResponse()
    {
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();
        $sessionIdentifier = $session->getId();

        $session->close();

        $session->resume();

        self::assertNotNull($session->getSessionCookie());
        self::assertEquals($sessionIdentifier, $session->getSessionCookie()->getValue());
    }

    /**
     * Assures that no exception is thrown if a session is resumed.
     *
     * @test
     */
    public function resumeOnAStartedSessionDoesNotDoAnyHarm()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();

        $session->resume();

        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function startPutsACookieIntoTheHttpResponse()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();

        self::assertNotNull($session->getSessionCookie());
        self::assertEquals($session->getId(), $session->getSessionCookie()->getValue());
    }

    /**
     * @test
     */
    public function getIdReturnsTheCurrentSessionIdentifier()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        try {
            $session->getId();
            $this->fail('No exception thrown although the session was not started yet.');
        } catch (SessionNotStartedException $e) {
            $session->start();
            self::assertEquals(32, strlen($session->getId()));
        }
    }

    /**
     * @test
     */
    public function renewIdSetsANewSessionIdentifier()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());
        $session->start();

        $oldSessionId = $session->getId();
        $session->renewId();
        $newSessionId = $session->getId();
        self::assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * @test
     */
    public function renewIdThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());
        $session->renewId();
    }

    /**
     * @test
     */
    public function renewIdThrowsExceptionIfCalledOnRemoteSession()
    {
        $this->expectException(OperationNotSupportedException::class);
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());
        $session->renewId();
    }

    /**
     * @test
     */
    public function sessionDataCanBeRetrievedEvenAfterSessionIdHasBeenRenewed()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore =$this->createSessionDataStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $session->start();
        $session->putData('foo', 'bar');
        $session->renewId();

        $sessionCookie = $session->getSessionCookie();
        $session->close();

        $session = Session::createFromCookieAndSessionInformation($sessionCookie, '12345', time());
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $session->resume();

        self::assertEquals('bar', $session->getData('foo'));
    }

    /**
     * @test
     */
    public function getDataThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->getData('some key');
    }

    /**
     * @test
     */
    public function putDataThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->putData('some key', 'some value');
    }

    /**
     * @test
     */
    public function putDataThrowsExceptionIfTryingToPersistAResource()
    {
        $this->expectException(DataNotSerializableException::class);
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();
        $resource = fopen(__FILE__, 'r');
        $session->putData('some key', $resource);
    }

    /**
     * @test
     */
    public function getDataReturnsDataPreviouslySetWithPutData()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();

        self::assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');
        self::assertEquals('some value', $session->getData('some key'));
        self::assertTrue($session->hasKey('some key'));
    }

    /**
     * @test
     */
    public function hasKeyThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->hasKey('foo');
    }

    /**
     * @test
     */
    public function twoSessionsDontConflictIfUsingSameEntryIdentifiers()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $session1 = Session::create();
        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session1, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session1, 'sessionDataStore', $sessionDataStore);
        $session1->start();

        $session2 = Session::create();
        $this->inject($session2, 'settings', $this->settings);
        $this->inject($session2, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session2, 'sessionDataStore', $sessionDataStore);
        $session2->start();

        $session1->putData('foo', 'bar');
        $session2->putData('foo', 'baz');

        self::assertEquals('bar', $session1->getData('foo'));
        self::assertEquals('baz', $session2->getData('foo'));
    }

    /**
     * @test
     */
    public function getLastActivityTimestampThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->getLastActivityTimestamp();
    }

    /**
     * @test
     */
    public function lastActivityTimestampOfNewSessionIsSetAndStoredCorrectlyAndCanBeRetrieved()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        /** @var Session $session */
        $session = $this->getAccessibleMock(Session::class, ['dummy']);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $now = $session->_get('now');

        $session->start();
        $sessionIdentifier = $session->getId();
        self::assertEquals($now, $session->getLastActivityTimestamp());

        $session->close();

        $sessionInfo = $sessionMetaDataStore->findBySessionIdentifier($sessionIdentifier);
        self::assertEquals($now, $sessionInfo->getLastActivityTimestamp());
    }

    /**
     * @test
     */
    public function addTagThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->addTag('MyTag');
    }

    /**
     * @test
     */
    public function addTagThrowsExceptionIfTagIsNotValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $taggedSession = Session::create();
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($taggedSession, 'sessionDataStore', $this->createSessionDataStore());
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->start();

        $taggedSession->addTag('Invalid Tag Contains Spaces');
    }

    /**
     * @test
     */
    public function aSessionCanBeTaggedAndBeRetrievedAgainByTheseTags()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $otherSession = Session::create();
        $this->inject($otherSession, 'settings', $this->settings);
        $this->inject($otherSession, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($otherSession, 'sessionDataStore', $sessionDataStore);
        $this->inject($otherSession, 'objectManager', $this->mockObjectManager);
        $otherSession->start();

        $taggedSession = Session::create();
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($taggedSession, 'sessionDataStore', $sessionDataStore);
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
        $this->inject($sessionManager, 'sessionMetaDataStore', $sessionMetaDataStore);

        $retrievedSessions = $sessionManager->getSessionsByTag('SampleTag');
        self::assertSame($taggedSessionId, $retrievedSessions[0]->getId());
        self::assertEquals(['SampleTag', 'AnotherTag'], $retrievedSessions[0]->getTags());
    }

    /**
     * @test
     */
    public function getActiveSessionsReturnsAllActiveSessions()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $sessions = [];
        $sessionIDs = [];
        for ($i = 0; $i < 5; $i++) {
            $session = Session::create();
            $this->inject($session, 'settings', $this->settings);
            $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
            $this->inject($session, 'sessionDataStore', $sessionDataStore);
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $session->start();
            $sessions[] = $session;
            $sessionIDs[] = $session->getId();
            $session->close();
        }

        $sessionManager = new SessionManager();
        $this->inject($sessionManager, 'sessionMetaDataStore', $sessionMetaDataStore);

        $activeSessions = $sessionManager->getActiveSessions();

        self::assertCount(5, $activeSessions);

        /* @var $randomActiveSession Session */
        $randomActiveSession = $activeSessions[array_rand($activeSessions)];
        $randomActiveSession->resume();

        self::assertContains($randomActiveSession->getId(), $sessionIDs);
    }

    /**
     * @test
     */
    public function getTagsOnAResumedSessionReturnsTheTagsSetWithAddTag()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $session->start();
        $session->addTag('SampleTag');
        $session->addTag('AnotherTag');

        $sessionCookie = $session->getSessionCookie();

        $session->close();


        $session = Session::createFromCookieAndSessionInformation($sessionCookie, '12345', time());
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);
        self::assertNotNull($session->resume(), 'The session was not properly resumed.');

        self::assertEquals(['SampleTag', 'AnotherTag'], $session->getTags());
    }

    /**
     * @test
     */
    public function getTagsThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->getTags();
    }

    /**
     * @test
     */
    public function removeTagThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->removeTag('MyTag');
    }

    /**
     * @test
     */
    public function removeTagRemovesAPreviouslySetTag()
    {
        $taggedSession = Session::create();
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($taggedSession, 'sessionDataStore', $this->createSessionDataStore());
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->start();

        $taggedSession->addTag('SampleTag');
        $taggedSession->addTag('AnotherTag');

        $taggedSession->removeTag('SampleTag');
        $taggedSession->addTag('YetAnotherTag');

        $taggedSession->removeTag('DoesntExistButDoesNotAnyHarm');

        self::assertEquals(['AnotherTag', 'YetAnotherTag'], array_values($taggedSession->getTags()));
    }

    /**
     * @test
     */
    public function touchThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->touch();
    }

    /**
     * @test
     */
    public function touchUpdatesLastActivityTimestampOfRemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1110000000, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $this->inject($session, 'now', 2220000000);

        $session->touch();

        $sessionInfo = $sessionMetaDataStore->findBySessionIdentifier('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        self::assertEquals(2220000000, $sessionInfo->getLastActivityTimestamp());
        self::assertEquals($storageIdentifier, $sessionInfo->getStorageIdentifier());
    }

    /**
     * @test
     */
    public function closeFlagsTheSessionAsClosed()
    {
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();
        self::assertTrue($session->isStarted());

        $session->close();
        self::assertFalse($session->isStarted());
    }

    /**
     * @test
     */
    public function closeAndShutdownObjectDoNotCloseARemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());

        $session->start();
        self::assertTrue($session->isStarted());

        $session->close();
        self::assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function shutdownChecksIfSessionStillExistsInStorageCacheBeforeWritingData()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        // Start a "local" session and store some data:
        $session->start();
        $sessionIdentifier = $session->getId();

        $session->putData('foo', 'bar');
        $session->close();
        $sessionInfo = $sessionMetaDataStore->findBySessionIdentifier($sessionIdentifier);

        // Simulate a remote server referring to the same session:
        $remoteSession = Session::createRemote($sessionIdentifier, $sessionInfo->getStorageIdentifier(), $sessionInfo->getLastActivityTimestamp(), []);
        $this->inject($remoteSession, 'objectManager', $this->mockObjectManager);
        $this->inject($remoteSession, 'settings', $this->settings);
        $this->inject($remoteSession, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($remoteSession, 'sessionDataStore', $sessionDataStore);

        // Resume the local session and add more data:
        self::assertTrue($sessionMetaDataStore->has($sessionIdentifier));
        $session->resume();
        $session->putData('baz', 'quux');

        // The remote server destroys the local session in the meantime:
        $remoteSession->destroy();

        // Close the local session – this must not write any data because the session doesn't exist anymore:
        $session->close();

        self::assertFalse($sessionMetaDataStore->has($sessionIdentifier));
    }

    /**
     * @test
     */
    public function destroyThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->destroy();
    }

    /**
     * @test
     */
    public function destroyRemovesAllSessionDataFromTheCurrentSessionButNotFromOtherSessions()
    {
        $session1 = Session::create();
        $session2 = Session::create();

        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session2, 'settings', $this->settings);

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $this->inject($session1, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session1, 'sessionDataStore', $sessionDataStore);
        $this->inject($session2, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session2, 'sessionDataStore', $sessionDataStore);

        $session1->start();
        $session2->start();

        $metadata1 = $sessionMetaDataStore->findBySessionIdentifier($session1->getId());
        $metadata2 = $sessionMetaDataStore->findBySessionIdentifier($session2->getId());

        $session1->putData('session 1 key 1', 'some value');
        $session1->putData('session 1 key 2', 'some other value');
        $session2->putData('session 2 key', 'some value');

        self::assertTrue($sessionDataStore->has($metadata1, 'session 1 key 1'));
        self::assertTrue($sessionDataStore->has($metadata1, 'session 1 key 2'));
        self::assertTrue($sessionDataStore->has($metadata2, 'session 2 key'));

        $session1->destroy(__METHOD__);

        $this->inject($session1, 'started', true);
        $this->inject($session2, 'started', true);

        self::assertFalse($sessionDataStore->has($metadata1, 'session 1 key 1'));
        self::assertFalse($sessionDataStore->has($metadata1, 'session 1 key 2'));
        self::assertTrue($sessionDataStore->has($metadata2, 'session 2 key'), 'Entry in session was also removed.');
    }

    /**
     * @test
     */
    public function destroyRemovesAllSessionDataFromARemoteSession()
    {
        $sessionMetaData = new SessionMetaData(
            'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb',
            '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16',
            1354293259,
            []
        );

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        $session = Session::createRemoteFromSessionMetaData($sessionMetaData);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $session->start();

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        $session->destroy(__METHOD__);

        self::assertFalse($sessionDataStore->has($sessionMetaData, 'session 1 key 1'));
        self::assertFalse($sessionDataStore->has($sessionMetaData, 'session 1 key 2'));
    }

    /**
     * @test
     */
    public function autoExpireRemovesAllSessionDataOfTheExpiredSession()
    {
        $session = $this->getAccessibleMock(Session::class, ['dummy']);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);

        $session->start();
        $sessionIdentifier = $session->getId();
        $sessionMetaData = $session->_get('sessionMetaData');
        $storageIdentifier = $sessionMetaData->getStorageIdentifier();

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        self::assertTrue($sessionDataStore->has($sessionMetaData, 'session 1 key 1'));
        self::assertTrue($sessionDataStore->has($sessionMetaData, 'session 1 key 2'));

        $session->close();

        $sessionInfo = $sessionMetaDataStore->findBySessionIdentifier($sessionIdentifier);
        $sessionInfo  = $sessionInfo->withLastActivityTimestamp(time() - 4000);
        $sessionMetaDataStore->store($sessionInfo);

        // canBeResumed implicitly calls autoExpire():
        self::assertFalse($session->canBeResumed(), 'canBeResumed');

        self::assertFalse($sessionDataStore->has($sessionMetaData, 'session 1 key 1'));
        self::assertFalse($sessionDataStore->has($sessionMetaData, 'session 1 key 2'));
    }

    /**
     * @test
     */
    public function autoExpireTriggersGarbageCollectionForExpiredSessions()
    {
        $settings = $this->settings;
        $settings['session']['inactivityTimeout'] = 5000;
        $settings['session']['garbageCollection']['probability'] = 100;

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionDataStore = $this->createSessionDataStore();

        // Create a session which first runs fine and then expires by later modifying
        // the inactivity timeout:
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionDataStore', $sessionDataStore);
        $session->injectSettings($settings);

        $session->start();
        $sessionIdentifier1 = $session->getId();
        $session->putData('session 1 key 1', 'session 1 value 1');
        $session->putData('session 1 key 2', 'session 1 value 2');
        $session->close();

        $session->resume();
        self::assertTrue($session->isStarted());
        self::assertTrue($sessionMetaDataStore->has($sessionIdentifier1), 'session 1 meta entry doesnt exist');
        $session->close();

        $sessionInfo1 = $sessionMetaDataStore->findBySessionIdentifier($sessionIdentifier1);
        $sessionInfo1 = $sessionInfo1->withLastActivityTimestamp(time() - 4000);
        $sessionMetaDataStore->store($sessionInfo1);

        // Because we change the timeout post factum, the previously valid session
        // now expires:
        $settings['session']['inactivityTimeout'] = 3000;

        // Create a second session which should remove the first expired session
        // implicitly by calling autoExpire()
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionDataStore', $this->createSessionDataStore());
        $session->injectSettings($settings);

        $session->start();
        $sessionIdentifier2 = $session->getId();
        $session->putData('session 2 key 1', 'session 1 value 1');
        $session->putData('session 2 key 2', 'session 1 value 2');
        $session->close();

        // Calls autoExpire() internally:
        $session->resume();

        $sessionInfo2 = $sessionMetaDataStore->findBySessionIdentifier($sessionIdentifier2);

        // Check how the cache looks like - data of session 1 should be gone:
        self::assertFalse($sessionMetaDataStore->has($sessionIdentifier1), 'session 1 meta entry still there');
        self::assertFalse($sessionDataStore->has($sessionInfo1, 'session 1 key 1'), 'session 1 key 1 still there');
        self::assertFalse($sessionDataStore->has($sessionInfo1, 'session 1 key 2'), 'session 1 key 2 still there');
        self::assertTrue($sessionDataStore->has($sessionInfo2, 'session 2 key 1'), 'session 2 key 1 not there');
        self::assertTrue($sessionDataStore->has($sessionInfo2, 'session 2 key 2'), 'session 2 key 2 not there');
    }

    protected function createSessionDataStore(): SessionDataStore
    {
        $backend = new FileBackend(new EnvironmentConfiguration('Session Testing', 'vfs://Foo/', PHP_MAXPATHLEN));
        $cache = new StringFrontend('Storage', $backend);
        $cache->initializeObject();
        $backend->setCache($cache);
        $cache->flush();
        $store = new SessionDataStore();
        $store->injectCache($cache);
        return $store;
    }

    protected function createSessionMetaDataStore():SessionMetaDataStore
    {
        $backend = new FileBackend(new EnvironmentConfiguration('Session Testing', 'vfs://Foo/', PHP_MAXPATHLEN));
        $cache = new VariableFrontend('Meta', $backend);
        $cache->initializeObject();
        $backend->setCache($cache);
        $cache->flush();
        $store = new SessionMetaDataStore();
        $store->injectCache($cache);
        return $store;
    }
}

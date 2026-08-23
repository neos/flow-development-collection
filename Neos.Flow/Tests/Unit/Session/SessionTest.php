<?php

declare(strict_types=1);

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
use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\Data\SessionIdentifier;
use Neos\Flow\Session\Data\SessionKeyValueStore;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Flow\Session\Data\StorageIdentifier;
use Neos\Flow\Session\Exception\DataNotSerializableException;
use Neos\Flow\Session\Exception\OperationNotSupportedException;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\Session;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unit tests for the Flow Session implementation
 */
final class SessionTest extends UnitTestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest;

    /**
     * @var Context|MockObject
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

        $mockRequestHandler = $this->createMock(RequestHandler::class);
        $mockRequestHandler->method('getHttpRequest')->willReturn($this->httpRequest);

        $this->mockBootstrap = $this->createMock(Bootstrap::class);
        $this->mockBootstrap->expects(self::any())->method('getActiveRequestHandler')->willReturn($mockRequestHandler);

        $this->mockSecurityContext = $this->createMock(Context::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(Context::class)->willReturn($this->mockSecurityContext);
    }

    #[Test]
    public function constructCreatesARemoteSessionIfSessionIfIdentifierIsSpecified()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        self::assertTrue($session->isRemote());

        $session = Session::create();
        self::assertFalse($session->isRemote());
    }

    #[Test]
    public function remoteSessionUsesStorageIdentifierPassedToConstructor()
    {
        $sessionIdentifier = 'ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb';
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $metadata = new SessionMetaData(
            SessionIdentifier::createFromString($sessionIdentifier),
            StorageIdentifier::createFromString($storageIdentifier),
            1354293259,
            []
        );
        $session = Session::createRemoteFromSessionMetaData($metadata);

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        self::assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');

        self::assertEquals('some value', $session->getData('some key'));
        self::assertTrue($session->hasKey('some key'));

        self::assertTrue($sessionKeyValueStore->has($metadata->storageIdentifier, 'some key'));
    }

    #[Test]
    public function canBeResumedReturnsFalseIfNoSessionCookieExists()
    {
        $session = Session::create();
        self::assertFalse($session->canBeResumed());
    }

    #[Test]
    public function canBeResumedReturnsFalseIfTheSessionHasAlreadyBeenStarted()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();

        self::assertFalse($session->canBeResumed());
    }

    #[Test]
    public function canBeResumedReturnsFalseIfSessionIsExpiredAndExpiresASessionIfLastActivityIsOverTheLimit()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session->start();
        $sessionIdentifierString = $session->getId();
        $sessionIdentifier = SessionIdentifier::createFromString($sessionIdentifierString);
        $session->close();

        self::assertTrue($session->canBeResumed());

        $sessionInfo = $sessionMetaDataStore->retrieve($sessionIdentifier);
        $sessionInfo = $sessionInfo->withLastActivityTimestamp(time() - 4000);
        $sessionMetaDataStore->store($sessionInfo);
        self::assertFalse($session->canBeResumed());
    }

    #[Test]
    public function isStartedReturnsFalseByDefault()
    {
        $session = Session::create();
        self::assertFalse($session->isStarted());
    }

    #[Test]
    public function isStartedReturnsTrueAfterSessionHasBeenStarted()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();
        self::assertTrue($session->isStarted());
    }

    #[Test]
    public function resumeSetsSessionCookieInTheResponse()
    {
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();
        $sessionIdentifier = $session->getId();

        $session->close();

        $session->resume();

        self::assertNotNull($session->getSessionCookie());
        self::assertEquals($sessionIdentifier, $session->getSessionCookie()->getValue());
    }

    /**
     * Assures that no exception is thrown if a session is resumed.
     */
    #[Test]
    public function resumeOnAStartedSessionDoesNotDoAnyHarm()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();

        $session->resume();

        self::assertTrue(true);
    }

    #[Test]
    public function startPutsACookieIntoTheHttpResponse()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();

        self::assertNotNull($session->getSessionCookie());
        self::assertEquals($session->getId(), $session->getSessionCookie()->getValue());
    }

    #[Test]
    public function getIdReturnsTheCurrentSessionIdentifier()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        try {
            $session->getId();
            $this->fail('No exception thrown although the session was not started yet.');
        } catch (SessionNotStartedException $e) {
            $session->start();
            self::assertSame(32, strlen($session->getId()));
        }
    }

    #[Test]
    public function renewIdSetsANewSessionIdentifier()
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());
        $session->start();

        $oldSessionId = $session->getId();
        $session->renewId();
        $newSessionId = $session->getId();
        self::assertNotEquals($oldSessionId, $newSessionId);
    }

    #[Test]
    public function renewIdThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());
        $session->renewId();
    }

    #[Test]
    public function renewIdThrowsExceptionIfCalledOnRemoteSession()
    {
        $this->expectException(OperationNotSupportedException::class);
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());
        $session->renewId();
    }

    #[Test]
    public function sessionDataCanBeRetrievedEvenAfterSessionIdHasBeenRenewed()
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session->start();
        $session->putData('foo', 'bar');
        $session->renewId();

        $sessionCookie = $session->getSessionCookie();
        $session->close();

        $session = Session::createFromCookieAndSessionInformation($sessionCookie, '12345', time());
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session->resume();

        self::assertEquals('bar', $session->getData('foo'));
    }

    #[Test]
    public function getDataThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->getData('some key');
    }

    #[Test]
    public function putDataThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->putData('some key', 'some value');
    }

    /**
     * @throws
     */
    #[Test]
    public function putDataThrowsExceptionIfTryingToPersistAResource(): void
    {
        $this->expectException(DataNotSerializableException::class);
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();
        $resource = fopen(__FILE__, 'r');
        $session->putData('some key', $resource);
    }

    #[Test]
    public function getDataReturnsDataPreviouslySetWithPutData(): void
    {
        $session = Session::create();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();

        self::assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');
        self::assertEquals('some value', $session->getData('some key'));
        self::assertTrue($session->hasKey('some key'));
    }

    #[Test]
    public function hasKeyThrowsExceptionIfCalledOnNonStartedSession(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->hasKey('foo');
    }

    /**
     * @throws
     */
    #[Test]
    public function twoSessionsDontConflictIfUsingSameEntryIdentifiers(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session1 = Session::create();
        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session1, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session1, 'sessionKeyValueStore', $sessionKeyValueStore);
        $session1->start();

        $session2 = Session::create();
        $this->inject($session2, 'settings', $this->settings);
        $this->inject($session2, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session2, 'sessionKeyValueStore', $sessionKeyValueStore);
        $session2->start();

        $session1->putData('foo', 'bar');
        $session2->putData('foo', 'baz');

        self::assertEquals('bar', $session1->getData('foo'));
        self::assertEquals('baz', $session2->getData('foo'));
    }

    #[Test]
    public function getLastActivityTimestampThrowsExceptionIfCalledOnNonStartedSession(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->getLastActivityTimestamp();
    }

    /**
     * @throws
     */
    #[Test]
    public function lastActivityTimestampOfNewSessionIsSetAndStoredCorrectlyAndCanBeRetrieved(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        /** @var Session $session */
        $session = $this->getAccessibleMock(Session::class, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $now = $session->_get('now');

        $session->start();
        $sessionIdentifierString = $session->getId();
        $sessionIdentifier = SessionIdentifier::createFromString($sessionIdentifierString);
        self::assertEquals($now, $session->getLastActivityTimestamp());

        $session->close();

        $sessionInfo = $sessionMetaDataStore->retrieve($sessionIdentifier);
        self::assertEquals($now, $sessionInfo->lastActivityTimestamp);
    }

    #[Test]
    public function addTagThrowsExceptionIfCalledOnNonStartedSession(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->addTag('MyTag');
    }

    /**
     * @throws
     */
    #[Test]
    public function addTagThrowsExceptionIfTagIsNotValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $taggedSession = Session::create();
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($taggedSession, 'sessionKeyValueStore', $this->createSessionKeyValueStore());
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->start();

        $taggedSession->addTag('Invalid Tag Contains Spaces');
    }

    /**
     * @throws
     */
    #[Test]
    public function aSessionCanBeTaggedAndBeRetrievedAgainByTheseTags(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $otherSession = Session::create();
        $this->inject($otherSession, 'settings', $this->settings);
        $this->inject($otherSession, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($otherSession, 'sessionKeyValueStore', $sessionKeyValueStore);
        $this->inject($otherSession, 'objectManager', $this->mockObjectManager);
        $otherSession->start();

        $taggedSession = Session::create();
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($taggedSession, 'sessionKeyValueStore', $sessionKeyValueStore);
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->start();
        $taggedSessionId = $taggedSession->getId();

        $otherSession->putData('foo', 'bar');
        $taggedSession->putData('foo', 'baz');

        $taggedSession->addTag('SampleTag');
        $taggedSession->addTag('AnotherTag');

        $otherSession->close();
        $taggedSession->close();

        $sessionManager = new SessionManager(
            $sessionMetaDataStore,
            $sessionKeyValueStore,
            0.2,
            5,
            1000
        );

        $retrievedSessions = $sessionManager->getSessionsByTag('SampleTag');
        self::assertSame($taggedSessionId, $retrievedSessions[0]->getId());
        self::assertEquals(['SampleTag', 'AnotherTag'], $retrievedSessions[0]->getTags());
    }

    /**
     * @throws
     */
    #[Test]
    public function getActiveSessionsReturnsAllActiveSessions(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $sessionIDs = [];
        for ($i = 0; $i < 5; $i++) {
            $session = Session::create();
            $this->inject($session, 'settings', $this->settings);
            $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
            $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $session->start();
            $sessionIDs[] = $session->getId();
            $session->close();
        }

        $sessionManager = new SessionManager(
            $sessionMetaDataStore,
            $sessionKeyValueStore,
            0.5,
            5,
            1000
        );

        $activeSessions = $sessionManager->getActiveSessions();

        self::assertCount(5, $activeSessions);

        /* @var $randomActiveSession Session */
        $randomActiveSession = $activeSessions[array_rand($activeSessions)];
        $randomActiveSession->resume();

        self::assertContains($randomActiveSession->getId(), $sessionIDs);
    }

    /**
     * @throws
     */
    #[Test]
    public function getTagsOnAResumedSessionReturnsTheTagsSetWithAddTag(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session->start();
        $session->addTag('SampleTag');
        $session->addTag('AnotherTag');

        $sessionCookie = $session->getSessionCookie();

        $session->close();

        $session = Session::createFromCookieAndSessionInformation($sessionCookie, '12345', time());
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);
        self::assertNotNull($session->resume(), 'The session was not properly resumed.');

        self::assertEquals(['SampleTag', 'AnotherTag'], $session->getTags());
    }

    #[Test]
    public function getTagsThrowsExceptionIfCalledOnNonStartedSession(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->getTags();
    }

    #[Test]
    public function removeTagThrowsExceptionIfCalledOnNonStartedSession(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->removeTag('MyTag');
    }

    /**
     * @throws
     */
    #[Test]
    public function removeTagRemovesAPreviouslySetTag(): void
    {
        $taggedSession = Session::create();
        $this->inject($taggedSession, 'settings', $this->settings);
        $this->inject($taggedSession, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($taggedSession, 'sessionKeyValueStore', $this->createSessionKeyValueStore());
        $this->inject($taggedSession, 'objectManager', $this->mockObjectManager);
        $taggedSession->start();

        $taggedSession->addTag('SampleTag');
        $taggedSession->addTag('AnotherTag');

        $taggedSession->removeTag('SampleTag');
        $taggedSession->addTag('YetAnotherTag');

        $taggedSession->removeTag('DoesntExistButDoesNotAnyHarm');

        self::assertSame(['AnotherTag', 'YetAnotherTag'], array_values($taggedSession->getTags()));
    }

    #[Test]
    public function touchThrowsExceptionIfCalledOnNonStartedSession(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->touch();
    }

    /**
     * @throws
     */
    #[Test]
    public function touchUpdatesLastActivityTimestampOfRemoteSession(): void
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1110000000, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $this->inject($session, 'now', 2220000000);

        $session->touch();

        $sessionInfo = $sessionMetaDataStore->retrieve(SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb'));
        self::assertEquals(2220000000, $sessionInfo->lastActivityTimestamp);
        self::assertEquals($storageIdentifier, $sessionInfo->storageIdentifier->value);
    }

    /**
     * @throws
     */
    #[Test]
    public function closeFlagsTheSessionAsClosed(): void
    {
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();
        self::assertTrue($session->isStarted());

        $session->close();
        self::assertFalse($session->isStarted());
    }

    /**
     * @throws
     */
    #[Test]
    public function closeAndShutdownObjectDoNotCloseARemoteSession(): void
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = Session::createRemote('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());

        $session->start();
        self::assertTrue($session->isStarted());

        $session->close();
        self::assertTrue($session->isStarted());
    }

    /**
     * @throws
     */
    #[Test]
    public function shutdownChecksIfSessionStillExistsInStorageCacheBeforeWritingData(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        // Start a "local" session and store some data:
        $session->start();
        $sessionIdentifierString = $session->getId();
        $sessionIdentifier = SessionIdentifier::createFromString($sessionIdentifierString);

        $session->putData('foo', 'bar');
        $session->close();
        $sessionInfo = $sessionMetaDataStore->retrieve($sessionIdentifier);

        // Simulate a remote server referring to the same session:
        $remoteSession = Session::createRemote($sessionIdentifierString, $sessionInfo->storageIdentifier->value, $sessionInfo->lastActivityTimestamp, []);
        $this->inject($remoteSession, 'objectManager', $this->mockObjectManager);
        $this->inject($remoteSession, 'settings', $this->settings);
        $this->inject($remoteSession, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($remoteSession, 'sessionKeyValueStore', $sessionKeyValueStore);

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

    #[Test]
    public function destroyThrowsExceptionIfSessionIsNotStarted(): void
    {
        $this->expectException(SessionNotStartedException::class);
        $session = Session::create();
        $session->destroy();
    }

    /**
     * @throws
     */
    #[Test]
    public function destroyRemovesAllSessionDataFromTheCurrentSessionButNotFromOtherSessions(): void
    {
        $session1 = Session::create();
        $session2 = Session::create();

        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session2, 'settings', $this->settings);

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $this->inject($session1, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session1, 'sessionKeyValueStore', $sessionKeyValueStore);
        $this->inject($session2, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session2, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session1->start();
        $session2->start();

        $metadata1 = $sessionMetaDataStore->retrieve(SessionIdentifier::createFromString($session1->getId()));
        $metadata2 = $sessionMetaDataStore->retrieve(SessionIdentifier::createFromString($session2->getId()));

        $session1->putData('session 1 key 1', 'some value');
        $session1->putData('session 1 key 2', 'some other value');
        $session2->putData('session 2 key', 'some value');

        self::assertTrue($sessionKeyValueStore->has($metadata1->storageIdentifier, 'session 1 key 1'));
        self::assertTrue($sessionKeyValueStore->has($metadata1->storageIdentifier, 'session 1 key 2'));
        self::assertTrue($sessionKeyValueStore->has($metadata2->storageIdentifier, 'session 2 key'));

        $session1->destroy(__METHOD__);

        $this->inject($session1, 'started', true);
        $this->inject($session2, 'started', true);

        self::assertFalse($sessionKeyValueStore->has($metadata1->storageIdentifier, 'session 1 key 1'));
        self::assertFalse($sessionKeyValueStore->has($metadata1->storageIdentifier, 'session 1 key 2'));
        self::assertTrue($sessionKeyValueStore->has($metadata2->storageIdentifier, 'session 2 key'), 'Entry in session was also removed.');
    }

    /**
     * @throws
     */
    #[Test]
    public function destroyRemovesAllSessionDataFromARemoteSession(): void
    {
        $sessionMetaData = new SessionMetaData(
            SessionIdentifier::createFromString('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb'),
            StorageIdentifier::createFromString('6e988eaa-7010-4ee8-bfb8-96ea4b40ec16'),
            1354293259,
            []
        );

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $session = Session::createRemoteFromSessionMetaData($sessionMetaData);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session->start();

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        $session->destroy(__METHOD__);

        self::assertFalse($sessionKeyValueStore->has($sessionMetaData->storageIdentifier, 'session 1 key 1'));
        self::assertFalse($sessionKeyValueStore->has($sessionMetaData->storageIdentifier, 'session 1 key 2'));
    }

    /**
     * @throws
     */
    #[Test]
    public function autoExpireRemovesAllSessionDataOfTheExpiredSession(): void
    {
        $session = $this->getAccessibleMock(Session::class, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);

        $session->start();
        $sessionIdentifierString = $session->getId();
        $sessionIdentifier = SessionIdentifier::createFromString($sessionIdentifierString);
        /**
         * @var SessionMetaData $sessionMetaData
         */
        $sessionMetaData = $session->_get('sessionMetaData');

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        self::assertTrue($sessionKeyValueStore->has($sessionMetaData->storageIdentifier, 'session 1 key 1'));
        self::assertTrue($sessionKeyValueStore->has($sessionMetaData->storageIdentifier, 'session 1 key 2'));

        $session->close();

        $sessionInfo = $sessionMetaDataStore->retrieve($sessionIdentifier);
        $sessionInfo = $sessionInfo->withLastActivityTimestamp(time() - 4000);
        $sessionMetaDataStore->store($sessionInfo);

        // canBeResumed implicitly calls autoExpire():
        self::assertFalse($session->canBeResumed(), 'canBeResumed');

        self::assertFalse($sessionKeyValueStore->has($sessionMetaData->storageIdentifier, 'session 1 key 1'));
        self::assertFalse($sessionKeyValueStore->has($sessionMetaData->storageIdentifier, 'session 1 key 2'));
    }

    #[Test]
    public function autoExpireTriggersGarbageCollectionForExpiredSessions(): void
    {
        $settings = $this->settings;
        $settings['session']['inactivityTimeout'] = 5000;
        $settings['session']['garbageCollection']['probability'] = 100;

        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        // Create a session which first runs fine and then expires by later modifying
        // the inactivity timeout:
        $session = Session::create();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
        $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);
        $session->injectSettings($settings);

        $session->start();
        $sessionIdentifier1String = $session->getId();
        $sessionIdentifier1 = SessionIdentifier::createFromString($sessionIdentifier1String);
        $session->putData('session 1 key 1', 'session 1 value 1');
        $session->putData('session 1 key 2', 'session 1 value 2');
        $session->close();

        $session->resume();
        self::assertTrue($session->isStarted());
        self::assertTrue($sessionMetaDataStore->has($sessionIdentifier1), 'session 1 meta entry doesn\'t exist');
        $session->close();

        $sessionInfo1 = $sessionMetaDataStore->retrieve($sessionIdentifier1);
        $sessionInfo1 = $sessionInfo1->withLastActivityTimestamp(time() - 4000);
        $sessionMetaDataStore->store($sessionInfo1);

        // Because we change the timeout post factum, the previously valid session
        // now expires:
        $settings['session']['inactivityTimeout'] = 3000;

        // Create a second session which should remove the first expired session
        // implicitly by calling autoExpire()
        $session = Session::create();
        $session = $this->getAccessibleMock(Session::class, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'sessionMetaDataStore', $this->createSessionMetaDataStore());
        $this->inject($session, 'sessionKeyValueStore', $this->createSessionKeyValueStore());
        $session->injectSettings($settings);

        $session->start();
        $sessionIdentifier2String = $session->getId();
        $sessionIdentifier2 = SessionIdentifier::createFromString($sessionIdentifier2String);
        $session->putData('session 2 key 1', 'session 1 value 1');
        $session->putData('session 2 key 2', 'session 1 value 2');
        $session->close();

        // Calls autoExpire() internally:
        $session->resume();

        $sessionInfo2 = $sessionMetaDataStore->retrieve($sessionIdentifier2);

        // Check how the cache looks like - data of session 1 should be gone:
        self::assertFalse($sessionMetaDataStore->has($sessionIdentifier1), 'session 1 meta entry still there');
        self::assertFalse($sessionKeyValueStore->has($sessionInfo1->storageIdentifier, 'session 1 key 1'), 'session 1 key 1 still there');
        self::assertFalse($sessionKeyValueStore->has($sessionInfo1->storageIdentifier, 'session 1 key 2'), 'session 1 key 2 still there');
        self::assertTrue($sessionKeyValueStore->has($sessionInfo2->storageIdentifier, 'session 2 key 1'), 'session 2 key 1 not there');
        self::assertTrue($sessionKeyValueStore->has($sessionInfo2->storageIdentifier, 'session 2 key 2'), 'session 2 key 2 not there');
    }

    protected function createSessionKeyValueStore(): SessionKeyValueStore
    {
        $backend = new FileBackend(new EnvironmentConfiguration('Session Testing', 'vfs://Foo/', PHP_MAXPATHLEN));
        $cache = new StringFrontend('Storage', $backend);
        $cache->initializeObject();
        $backend->setCache($cache);
        $cache->flush();
        $store = new SessionKeyValueStore();
        $store->injectCache($cache);
        return $store;
    }

    protected function createSessionMetaDataStore(): SessionMetaDataStore
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

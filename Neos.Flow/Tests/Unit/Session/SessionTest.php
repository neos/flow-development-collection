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
use Neos\Flow\Http\RequestHandler;
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
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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
        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259);
        self::assertTrue($session->isRemote());

        $session = new Session();
        self::assertFalse($session->isRemote());
    }

    /**
     * @test
     */
    public function constructRequiresAStorageIdentifierIfASessionIdentifierWasGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
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
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        self::assertFalse($session->hasKey('some key'));
        $session->putData('some key', 'some value');

        self::assertEquals('some value', $session->getData('some key'));
        self::assertTrue($session->hasKey('some key'));

        self::assertTrue($storageCache->has($storageIdentifier . md5('some key')));
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfNoSessionCookieExists()
    {
        $session = new Session();
        self::assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfTheSessionHasAlreadyBeenStarted()
    {
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        self::assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function canBeResumedReturnsFalseIfSessionIsExpiredAndExpiresASessionIfLastActivityIsOverTheLimit()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->start();
        $sessionIdentifier = $session->getId();
        $session->close();

        self::assertTrue($session->canBeResumed());

        $sessionInfo = $metaDataCache->get($sessionIdentifier);
        $sessionInfo['lastActivityTimestamp'] = time() - 4000;
        $metaDataCache->set($sessionIdentifier, $sessionInfo, [$sessionInfo['storageIdentifier'], 'session'], 0);
        self::assertFalse($session->canBeResumed());
    }

    /**
     * @test
     */
    public function isStartedReturnsFalseByDefault()
    {
        $session = new Session();
        self::assertFalse($session->isStarted());
    }

    /**
     * @test
     */
    public function isStartedReturnsTrueAfterSessionHasBeenStarted()
    {
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
        $session->start();
        self::assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function resumeSetsSessionCookieInTheResponse()
    {
        $session = new Session();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

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
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $session->resume();

        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function startPutsACookieIntoTheHttpResponse()
    {
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        self::assertNotNull($session->getSessionCookie());
        self::assertEquals($session->getId(), $session->getSessionCookie()->getValue());
    }

    /**
     * @test
     */
    public function getIdReturnsTheCurrentSessionIdentifier()
    {
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

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
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
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
        $session = new Session();
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();
        $session->renewId();
    }

    /**
     * @test
     */
    public function renewIdThrowsExceptionIfCalledOnRemoteSession()
    {
        $this->expectException(OperationNotSupportedException::class);
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';
        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
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
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->start();
        $session->putData('foo', 'bar');
        $session->renewId();

        $sessionCookie = $session->getSessionCookie();
        $session->close();

        $session = Session::createFromCookieAndSessionInformation($sessionCookie, '12345', time());
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->resume();

        self::assertEquals('bar', $session->getData('foo'));
    }

    /**
     * @test
     */
    public function getDataThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = new Session();
        $session->getData('some key');
    }

    /**
     * @test
     */
    public function putDataThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = new Session();
        $session->putData('some key', 'some value');
    }

    /**
     * @test
     */
    public function putDataThrowsExceptionIfTryingToPersistAResource()
    {
        $this->expectException(DataNotSerializableException::class);
        $session = new Session();
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
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

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
        $this->inject($session1, 'settings', $this->settings);
        $this->inject($session1, 'metaDataCache', $metaDataCache);
        $this->inject($session1, 'storageCache', $storageCache);
        $session1->initializeObject();
        $session1->start();

        $session2 = new Session();
        $this->inject($session2, 'settings', $this->settings);
        $this->inject($session2, 'metaDataCache', $metaDataCache);
        $this->inject($session2, 'storageCache', $storageCache);
        $session2->initializeObject();
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
        $session = $this->getAccessibleMock(Session::class, ['dummy']);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $now = $session->_get('now');

        $session->start();
        $sessionIdentifier = $session->getId();
        self::assertEquals($now, $session->getLastActivityTimestamp());

        $session->close();

        $sessionInfo = $metaDataCache->get($sessionIdentifier);
        self::assertEquals($now, $sessionInfo['lastActivityTimestamp']);
    }

    /**
     * @test
     */
    public function addTagThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = new Session();
        $session->addTag('MyTag');
    }

    /**
     * @test
     */
    public function addTagThrowsExceptionIfTagIsNotValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $taggedSession = new Session();
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
        $this->inject($otherSession, 'settings', $this->settings);
        $this->inject($otherSession, 'metaDataCache', $metaDataCache);
        $this->inject($otherSession, 'storageCache', $storageCache);
        $this->inject($otherSession, 'objectManager', $this->mockObjectManager);
        $otherSession->initializeObject();
        $otherSession->start();

        $taggedSession = new Session();
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
        self::assertSame($taggedSessionId, $retrievedSessions[0]->getId());
        self::assertEquals(['SampleTag', 'AnotherTag'], $retrievedSessions[0]->getTags());
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
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();

        $session->start();
        $session->addTag('SampleTag');
        $session->addTag('AnotherTag');

        $sessionCookie = $session->getSessionCookie();

        $session->close();


        $session = Session::createFromCookieAndSessionInformation($sessionCookie, '12345', time());
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->initializeObject();
        self::assertNotNull($session->resume(), 'The session was not properly resumed.');

        self::assertEquals(['SampleTag', 'AnotherTag'], $session->getTags());
    }

    /**
     * @test
     */
    public function getTagsThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = new Session();
        $session->getTags();
    }

    /**
     * @test
     */
    public function removeTagThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = new Session();
        $session->removeTag('MyTag');
    }

    /**
     * @test
     */
    public function removeTagRemovesAPreviouslySetTag()
    {
        $taggedSession = new Session();
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

        self::assertEquals(['AnotherTag', 'YetAnotherTag'], array_values($taggedSession->getTags()));
    }

    /**
     * @test
     */
    public function touchThrowsExceptionIfCalledOnNonStartedSession()
    {
        $this->expectException(SessionNotStartedException::class);
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
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $this->inject($session, 'now', 2220000000);

        $session->touch();

        $sessionInfo = $metaDataCache->get('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb');
        self::assertEquals(2220000000, $sessionInfo['lastActivityTimestamp']);
        self::assertEquals($storageIdentifier, $sessionInfo['storageIdentifier']);
    }

    /**
     * @test
     */
    public function closeFlagsTheSessionAsClosed()
    {
        $session = new Session();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

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
        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();
        self::assertTrue($session->isStarted());

        $session->close();
        self::assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function shutdownCreatesSpecialDataEntryForSessionWithAuthenticatedAccounts()
    {
        $session = new Session();
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

        $this->mockSecurityContext->expects(self::any())->method('isInitialized')->will(self::returnValue(true));
        $this->mockSecurityContext->expects(self::any())->method('getAuthenticationTokens')->will(self::returnValue([$token]));

        $sessionCookie = $session->getSessionCookie();
        $session->close();

        $session->resume();
        self::assertEquals(['MyProvider:admin'], $session->getData('Neos_Flow_Security_Accounts'));
    }

    /**
     * @test
     */
    public function shutdownChecksIfSessionStillExistsInStorageCacheBeforeWritingData()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $session = new Session();
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
        $this->inject($remoteSession, 'objectManager', $this->mockObjectManager);
        $this->inject($remoteSession, 'settings', $this->settings);
        $this->inject($remoteSession, 'metaDataCache', $metaDataCache);
        $this->inject($remoteSession, 'storageCache', $storageCache);
        $remoteSession->initializeObject();

        // Resume the local session and add more data:
        self::assertTrue($metaDataCache->has($sessionIdentifier));
        $session->resume();
        $session->putData('baz', 'quux');

        // The remote server destroys the local session in the meantime:
        $remoteSession->destroy();

        // Close the local session – this must not write any data because the session doesn't exist anymore:
        $session->close();

        self::assertFalse($metaDataCache->has($sessionIdentifier));
    }

    /**
     * @test
     */
    public function destroyThrowsExceptionIfSessionIsNotStarted()
    {
        $this->expectException(SessionNotStartedException::class);
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
        self::assertFalse($session1->hasKey('session 1 key 1'));
        self::assertFalse($session1->hasKey('session 1 key 2'));
        self::assertTrue($session2->hasKey('session 2 key'), 'Entry in session was also removed.');
    }

    /**
     * @test
     */
    public function destroyRemovesAllSessionDataFromARemoteSession()
    {
        $storageIdentifier = '6e988eaa-7010-4ee8-bfb8-96ea4b40ec16';

        $session = new Session('ZPjPj3A0Opd7JeDoe7rzUQYCoDMcxscb', $storageIdentifier, 1354293259, []);
        $this->inject($session, 'settings', $this->settings);
        $this->inject($session, 'metaDataCache', $this->createCache('Meta'));
        $this->inject($session, 'storageCache', $this->createCache('Storage'));
        $session->initializeObject();

        $session->start();

        $session->putData('session 1 key 1', 'some value');
        $session->putData('session 1 key 2', 'some other value');

        $session->destroy(__METHOD__);

        $this->inject($session, 'started', true);
        self::assertFalse($session->hasKey('session 1 key 1'));
        self::assertFalse($session->hasKey('session 1 key 2'));
    }

    /**
     * @test
     */
    public function autoExpireRemovesAllSessionDataOfTheExpiredSession()
    {
        /** @var Session $session */
        $session = $this->getAccessibleMock(Session::class, ['dummy']);
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

        self::assertTrue($storageCache->has($storageIdentifier . md5('session 1 key 1')));
        self::assertTrue($storageCache->has($storageIdentifier . md5('session 1 key 2')));

        $session->close();

        $sessionInfo = $metaDataCache->get($sessionIdentifier);
        $sessionInfo['lastActivityTimestamp'] = time() - 4000;
        $metaDataCache->set($sessionIdentifier, $sessionInfo, [$storageIdentifier, 'session'], 0);

        // canBeResumed implicitly calls autoExpire():
        self::assertFalse($session->canBeResumed(), 'canBeResumed');

        self::assertFalse($storageCache->has($storageIdentifier . md5('session 1 key 1')));
        self::assertFalse($storageCache->has($storageIdentifier . md5('session 1 key 2')));
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
        self::assertTrue($session->isStarted());
        self::assertTrue($metaDataCache->has($sessionIdentifier1), 'session 1 meta entry doesnt exist');
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
        self::assertFalse($metaDataCache->has($sessionIdentifier1), 'session 1 meta entry still there');
        self::assertFalse($storageCache->has($sessionInfo1['storageIdentifier'] . md5('session 1 key 1')), 'session 1 key 1 still there');
        self::assertFalse($storageCache->has($sessionInfo1['storageIdentifier'] . md5('session 1 key 2')), 'session 1 key 2 still there');
        self::assertTrue($storageCache->has($sessionInfo2['storageIdentifier'] . md5('session 2 key 1')), 'session 2 key 1 not there');
        self::assertTrue($storageCache->has($sessionInfo2['storageIdentifier'] . md5('session 2 key 2')), 'session 2 key 2 not there');
    }

    /**
     * @test for #1674
     */
    public function garbageCollectionWorksCorrectlyWithInvalidMetadataEntry()
    {
        $settings = $this->settings;

        $metaDataCache = $this->createCache('Meta');
        $metaDataCache->set('foo', null);
        $storageCache = $this->createCache('Storage');

        $session = new Session();
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $this->inject($session, 'logger', $this->createMock(LoggerInterface::class));
        $session->injectSettings($settings);
        $session->initializeObject();

        $this->assertSame(0, $session->collectGarbage());
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
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->injectSettings($settings);
        $session->initializeObject();

        self::assertSame(0, $session->collectGarbage());
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
        $this->inject($session, 'objectManager', $this->mockObjectManager);
        $this->inject($session, 'metaDataCache', $metaDataCache);
        $this->inject($session, 'storageCache', $storageCache);
        $session->injectSettings($settings);
        $session->initializeObject();

        // No sessions need to be removed:
        self::assertSame(0, $session->collectGarbage());

        $metaDataCache->set('_garbage-collection-running', true, [], 120);

        // Session garbage collection is omitted:
        self::assertFalse($session->collectGarbage());
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
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $this->inject($session, 'metaDataCache', $metaDataCache);
            $this->inject($session, 'storageCache', $storageCache);
            $session->injectSettings($settings);
            $this->inject($session, 'logger', $this->createMock(LoggerInterface::class));
            $session->initializeObject();

            $session->start();
            $sessionIdentifier = $session->getId();
            $session->putData('foo', 'bar');
            $session->close();

            $sessionInfo = $metaDataCache->get($sessionIdentifier);
            $sessionInfo['lastActivityTimestamp'] = time() - 4000;
            $metaDataCache->set($sessionIdentifier, $sessionInfo, ['session'], 0);
        }

        self::assertLessThanOrEqual(5, $session->collectGarbage());
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

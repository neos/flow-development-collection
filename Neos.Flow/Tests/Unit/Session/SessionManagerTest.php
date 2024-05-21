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
use Neos\Flow\Session\Data\SessionMetaDataStore;
use Neos\Flow\Session\Session;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unit tests for the Flow Session implementation
 */
class SessionManagerTest extends UnitTestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest;

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

        $mockRequestHandler = $this->createMock(RequestHandler::class);
        $mockRequestHandler->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->httpRequest));

        $this->mockBootstrap = $this->createMock(Bootstrap::class);
        $this->mockBootstrap->expects(self::any())->method('getActiveRequestHandler')->will(self::returnValue($mockRequestHandler));

        $this->mockSecurityContext = $this->createMock(Context::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('get')->with(Context::class)->will(self::returnValue($this->mockSecurityContext));
    }

    /**
     * @test for #1674
     * @throws
     */
    public function garbageCollectionWorksCorrectlyWithInvalidMetadataEntry(): void
    {
        $cache = $this->createCache('Meta');
        $cache->set('foo', null);

        $sessionMetaDataStore = new SessionMetaDataStore();
        $sessionMetaDataStore->injectCache($cache);
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $sessionManager = new SessionManager(
            $sessionMetaDataStore,
            $sessionKeyValueStore,
            1.0,
            100,
            500
        );

        $this->assertSame(0, $sessionManager->collectGarbage());
    }

    /**
     * @test
     * @throws
     */
    public function garbageCollectionIsOmittedIfInactivityTimeoutIsSetToZero(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $sessionManager = new SessionManager(
            $sessionMetaDataStore,
            $sessionKeyValueStore,
            1.0,
            100,
            0
        );

        self::assertSame(0, $sessionManager->collectGarbage());
    }

    /**
     * @test
     * @throws
     */
    public function garbageCollectionIsOmittedIfAnotherProcessIsAlreadyRunning(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        $sessionManager = new SessionManager(
            $sessionMetaDataStore,
            $sessionKeyValueStore,
            100.0,
            100,
            5000
        );

        // No sessions need to be removed:
        self::assertSame(0, $sessionManager->collectGarbage());

        $sessionMetaDataStore->startGarbageCollection();

        // Session garbage collection is omitted:
        self::assertNull($sessionManager->collectGarbage());
    }

    /**
     * @test
     * @throws
     */
    public function garbageCollectionOnlyRemovesTheDefinedMaximumNumberOfSessions(): void
    {
        $sessionMetaDataStore = $this->createSessionMetaDataStore();
        $sessionKeyValueStore = $this->createSessionKeyValueStore();

        for ($i = 0; $i < 9; $i++) {
            $sessionManager = new SessionManager(
                $sessionMetaDataStore,
                $sessionKeyValueStore,
                0,
                5,
                1000
            );

            $session = Session::create();
            $this->inject($session, 'sessionMetaDataStore', $sessionMetaDataStore);
            $this->inject($session, 'sessionKeyValueStore', $sessionKeyValueStore);
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $this->inject($session, 'settings', $this->settings);
            $session->start();
            $sessionIdentifier = $session->getId();
            $session->putData('foo', 'bar');
            $session->close();

            $sessionInfo = $sessionMetaDataStore->retrieve(SessionIdentifier::createFromString($sessionIdentifier));
            $sessionInfo = $sessionInfo->withLastActivityTimestamp(time() - 4000);
            $sessionMetaDataStore->store($sessionInfo);
        }

        self::assertLessThanOrEqual(5, $sessionManager->collectGarbage());
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

    /**
     * Creates a cache for testing
     *
     * @param string $name
     * @return VariableFrontend
     * @throws
     */
    protected function createCache(string $name): VariableFrontend
    {
        $backend = new FileBackend(new EnvironmentConfiguration('Session Testing', 'vfs://Foo/', PHP_MAXPATHLEN));
        $cache = new VariableFrontend($name, $backend);
        $cache->initializeObject();
        $backend->setCache($cache);
        $cache->flush();
        return $cache;
    }
}

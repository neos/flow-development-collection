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
use org\bovigo\vfs\vfsStream;
use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\Session;
use Neos\Flow\Session\SessionManager;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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
     * @test for #1674
     */
    public function garbageCollectionWorksCorrectlyWithInvalidMetadataEntry()
    {
        $metaDataCache = $this->createCache('Meta');
        $metaDataCache->set('foo', null);
        $storageCache = $this->createCache('Storage');

        $sessionManager = new SessionManager();
        $this->inject($sessionManager, 'metaDataCache', $metaDataCache);
        $this->inject($sessionManager, 'storageCache', $storageCache);
        $this->inject($sessionManager, 'logger', $this->createMock(LoggerInterface::class));

        $this->assertSame(0, $sessionManager->collectGarbage());
    }

    /**
     * @test
     */
    public function garbageCollectionIsOmittedIfInactivityTimeoutIsSetToZero()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $sessionManager = new SessionManager();
        $this->inject($sessionManager, 'metaDataCache', $metaDataCache);
        $this->inject($sessionManager, 'storageCache', $storageCache);
        $this->inject($sessionManager, 'inactivityTimeout', 0);

        self::assertSame(0, $sessionManager->collectGarbage());
    }

    /**
     * @test
     */
    public function garbageCollectionIsOmittedIfAnotherProcessIsAlreadyRunning()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        $sessionManager = new SessionManager();
        $this->inject($sessionManager, 'metaDataCache', $metaDataCache);
        $this->inject($sessionManager, 'storageCache', $storageCache);
        $this->inject($sessionManager, 'inactivityTimeout', 5000);
        $this->inject($sessionManager, 'garbageCollectionProbability', 100);

        // No sessions need to be removed:
        self::assertSame(0, $sessionManager->collectGarbage());

        $metaDataCache->set('_garbage-collection-running', true, [], 120);

        // Session garbage collection is omitted:
        self::assertNull($sessionManager->collectGarbage());
    }

    /**
     * @test
     */
    public function garbageCollectionOnlyRemovesTheDefinedMaximumNumberOfSessions()
    {
        $metaDataCache = $this->createCache('Meta');
        $storageCache = $this->createCache('Storage');

        for ($i = 0; $i < 9; $i++) {
            $sessionManager = new SessionManager();
            $this->inject($sessionManager, 'metaDataCache', $metaDataCache);
            $this->inject($sessionManager, 'storageCache', $storageCache);
            $this->inject($sessionManager, 'inactivityTimeout', 1000);
            $this->inject($sessionManager, 'garbageCollectionProbability', 0);
            $this->inject($sessionManager, 'garbageCollectionMaximumPerRun', 5);
            $this->inject($sessionManager, 'logger', $this->createMock(LoggerInterface::class));

            $session = new Session();
            $this->inject($session, 'metaDataCache', $metaDataCache);
            $this->inject($session, 'storageCache', $storageCache);
            $this->inject($session, 'objectManager', $this->mockObjectManager);
            $this->inject($session, 'settings', $this->settings);
            $session->start();
            $sessionIdentifier = $session->getId();
            $session->putData('foo', 'bar');
            $session->close();

            $sessionInfo = $metaDataCache->get($sessionIdentifier);
            $sessionInfo['lastActivityTimestamp'] = time() - 4000;
            $metaDataCache->set($sessionIdentifier, $sessionInfo, ['session'], 0);
        }

        self::assertLessThanOrEqual(5, $sessionManager->collectGarbage());
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

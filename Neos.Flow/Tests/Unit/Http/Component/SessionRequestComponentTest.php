<?php
namespace Neos\Flow\Tests\Unit\Http\Component;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Session\Http\SessionRequestComponent;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the SessionRequestComponent
 */
class SessionRequestComponentTest extends UnitTestCase
{

    /**
     * @var SessionRequestComponent
     */
    private $sessionRequestComponent;

    /**
     * @var SessionManager|MockObject
     */
    private $mockSessionManager;

    /**
     * @var ServerRequestInterface|MockObject
     */
    private $mockHttpRequest;

    /**
     * @var ComponentContext|MockObject
     */
    private $mockComponentContext;

    /**
     * @var array
     */
    private $defaultSessionCookieSettings = [
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'domain' => null,
        'samesite' => null,
    ];

    public function setUp(): void
    {
        $this->sessionRequestComponent = new SessionRequestComponent();

        $this->mockSessionManager = $this->getMockBuilder(SessionManager::class)->disableOriginalConstructor()->getMock();
        $this->mockSessionManager->method('getCurrentSession')->willReturn($this->getMockBuilder(SessionInterface::class)->getMock());
        $this->inject($this->sessionRequestComponent, 'sessionManager', $this->mockSessionManager);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->method('getHttpRequest')->willReturn($this->mockHttpRequest);


        $this->inject($this->sessionRequestComponent, 'sessionSettings', [
            'name' => 'session_cookie_name',
            'cookie' => $this->defaultSessionCookieSettings,
        ]);
    }

    /**
     * @test
     */
    public function handleCreatesSessionIfNoCookiesAreSet(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([]);

        $this->mockSessionManager->expects($this->once())->method('createCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) {
            self::assertSame('session_cookie_name', $cookie->getName());
        });

        $this->sessionRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleCreatesSessionIfNoSessionCookieIsSet(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'some_cookie' => 'some_value',
            'some_other_cookie' => 'some other value',
        ]);

        $this->mockSessionManager->expects($this->once())->method('createCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) {
            self::assertSame('session_cookie_name', $cookie->getName());
        });

        $this->sessionRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleCreatesSessionIfSessionCookieIsNull(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'session_cookie_name' => null,
        ]);

        $this->mockSessionManager->expects($this->once())->method('createCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) {
            self::assertSame('session_cookie_name', $cookie->getName());
        });

        $this->sessionRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleInitializesSessionFromSessionCookieIfItExists(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'session_cookie_name' => 'some_value',
        ]);

        $this->mockSessionManager->expects($this->once())->method('initializeCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) {
            self::assertSame('session_cookie_name', $cookie->getName());
        });

        $this->sessionRequestComponent->handle($this->mockComponentContext);
    }

    public function sessionCookieSettingsProvider(): array
    {
        return [
            ['sessionCookieSettings' => [], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; HttpOnly'],
            ['sessionCookieSettings' => ['lifetime' => 123], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Max-Age=123; Path=/; HttpOnly'],
            ['sessionCookieSettings' => ['path' => '/some/path'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/some/path; HttpOnly'],
            ['sessionCookieSettings' => ['secure' => true], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; Secure; HttpOnly'],
            ['sessionCookieSettings' => ['httponly' => false], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/'],
            ['sessionCookieSettings' => ['domain' => 'neos.io'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Domain=neos.io; Path=/; HttpOnly'],
            ['sessionCookieSettings' => ['samesite' => 'none'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; Secure; HttpOnly; SameSite=none'],
            ['sessionCookieSettings' => ['samesite' => 'strict'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; HttpOnly; SameSite=strict'],
            ['sessionCookieSettings' => ['samesite' => 'lax'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; HttpOnly; SameSite=lax'],
        ];
    }

    /**
     * @test
     * @dataProvider sessionCookieSettingsProvider
     */
    public function newSessionCookiesTakeSessionCookieSettingsIntoAccount(array $sessionCookieSettings, string $expectedCookie): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn(['session_cookie_name' => 'session-id']);

        $this->inject($this->sessionRequestComponent, 'sessionSettings', [
            'name' => 'session_cookie_name',
            'cookie' => array_merge($this->defaultSessionCookieSettings, $sessionCookieSettings),
        ]);

        $this->mockSessionManager->expects($this->once())->method('initializeCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) use ($expectedCookie) {
            self::assertSame($expectedCookie, (string)$cookie);
        });

        $this->sessionRequestComponent->handle($this->mockComponentContext);
    }

    public function cookieValueDataProvider(): array
    {
        return [
            ['sessionCookieValue' => 123, 'expectedNewCookieValue' => '123'],
            ['sessionCookieValue' => '', 'expectedNewCookieValue' => ''],
            ['sessionCookieValue' => 'some String', 'expectedNewCookieValue' => 'some String'],
            ['sessionCookieValue' => '"leading quote', 'expectedNewCookieValue' => 'leading quote'],
            ['sessionCookieValue' => 'trailing quote"', 'expectedNewCookieValue' => 'trailing quote'],
            ['sessionCookieValue' => '"quotes"', 'expectedNewCookieValue' => 'quotes'],
            ['sessionCookieValue' => '""double quotes"', 'expectedNewCookieValue' => 'double quotes'],
            ['sessionCookieValue' => '%22encoded quotes%22', 'expectedNewCookieValue' => 'encoded quotes'],

            // Note: The following test cases merely document the status quo.
            // The cookie values are valid according to https://tools.ietf.org/html/rfc6265#section-4.1.1 but we might want to tweak the behavior in the future
            ['sessionCookieValue' => '   whitespace   ', 'expectedNewCookieValue' => '   whitespace   '],
            ['sessionCookieValue' => "\t" . 'tabs' . "\t", 'expectedNewCookieValue' => '	tabs	'],
            ['sessionCookieValue' => 'semicolon;', 'expectedNewCookieValue' => 'semicolon;'],
            ['sessionCookieValue' => '%C3%BCrl%20encoded', 'expectedNewCookieValue' => 'ürl encoded'],
        ];
    }

    /**
     * @test
     * @dataProvider cookieValueDataProvider
     */
    public function valueFromSessionCookieIsCleanedBeforeANewCookieIsCreated($sessionCookieValue, $expectedNewCookieValue): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'session_cookie_name' => $sessionCookieValue,
        ]);

        $this->mockSessionManager->expects($this->once())->method('initializeCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) use ($expectedNewCookieValue) {
            self::assertSame($expectedNewCookieValue, $cookie->getValue());
        });

        $this->sessionRequestComponent->handle($this->mockComponentContext);
    }
}

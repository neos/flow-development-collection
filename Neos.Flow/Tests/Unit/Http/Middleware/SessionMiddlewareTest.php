<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Http\Middleware;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Http\Cookie;
use Neos\Flow\Http\Middleware\SessionMiddleware;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test case for the SessionMiddleware
 */
final class SessionMiddlewareTest extends UnitTestCase
{
    /**
     * @var SessionMiddleware
     */
    private $sessionMiddleware;

    /**
     * @var SessionManager|MockObject
     */
    private $mockSessionManager;

    /**
     * @var ServerRequestInterface|MockObject
     */
    private $mockHttpRequest;
    /**
     * @var array
     */
    private $defaultSessionCookieSettings = [
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'domain' => null,
        'samesite' => Cookie::SAMESITE_LAX,
    ];

    public function setUp(): void
    {
        $this->sessionMiddleware = new SessionMiddleware();

        $this->mockSessionManager = $this->createMock(SessionManager::class);
        $this->mockSessionManager->method('getCurrentSession')->willReturn($this->createMock(SessionInterface::class));
        $this->inject($this->sessionMiddleware, 'sessionManager', $this->mockSessionManager);

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);


        $this->inject($this->sessionMiddleware, 'sessionSettings', [
            'name' => 'session_cookie_name',
            'cookie' => $this->defaultSessionCookieSettings,
        ]);
    }

    #[Test]
    public function handleCreatesSessionIfNoCookiesAreSet(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([]);
        $this->mockSessionManager->expects($this->once())->method('createCurrentSessionFromCookie');

        $this->sessionMiddleware->process($this->mockHttpRequest, $this->createStub(RequestHandlerInterface::class));
    }

    #[Test]
    public function handleCreatesSessionIfNoSessionCookieIsSet(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'some_cookie' => 'some_value',
            'some_other_cookie' => 'some other value',
        ]);

        $this->mockSessionManager->expects($this->once())->method('createCurrentSessionFromCookie');

        $this->sessionMiddleware->process($this->mockHttpRequest, $this->createStub(RequestHandlerInterface::class));
    }

    #[Test]
    public function handleCreatesSessionIfSessionCookieIsNull(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'session_cookie_name' => null,
        ]);

        $this->mockSessionManager->expects($this->once())->method('createCurrentSessionFromCookie');

        $this->sessionMiddleware->process($this->mockHttpRequest, $this->createStub(RequestHandlerInterface::class));
    }

    #[Test]
    public function handleInitializesSessionFromSessionCookieIfItExists(): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'session_cookie_name' => 'some_value',
        ]);

        $this->mockSessionManager->expects($this->once())->method('initializeCurrentSessionFromCookie');

        $this->sessionMiddleware->process($this->mockHttpRequest, $this->createStub(RequestHandlerInterface::class));
    }

    public static function sessionCookieSettingsProvider(): \Iterator
    {
        yield ['sessionCookieSettings' => [], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; HttpOnly; SameSite=lax'];
        yield ['sessionCookieSettings' => ['lifetime' => 123], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Max-Age=123; Path=/; HttpOnly; SameSite=lax'];
        yield ['sessionCookieSettings' => ['path' => '/some/path'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/some/path; HttpOnly; SameSite=lax'];
        yield ['sessionCookieSettings' => ['secure' => true], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; Secure; HttpOnly; SameSite=lax'];
        yield ['sessionCookieSettings' => ['httponly' => false], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; SameSite=lax'];
        yield ['sessionCookieSettings' => ['domain' => 'neos.io'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Domain=neos.io; Path=/; HttpOnly; SameSite=lax'];
        yield ['sessionCookieSettings' => ['samesite' => 'none'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; Secure; HttpOnly; SameSite=none'];
        yield ['sessionCookieSettings' => ['samesite' => 'strict'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; HttpOnly; SameSite=strict'];
        yield ['sessionCookieSettings' => ['samesite' => 'lax'], 'expectedNewCookieValue' => 'session_cookie_name=session-id; Path=/; HttpOnly; SameSite=lax'];
    }

    #[DataProvider('sessionCookieSettingsProvider')]
    #[Test]
    public function newSessionCookiesTakeSessionCookieSettingsIntoAccount(array $sessionCookieSettings, string $expectedNewCookieValue): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn(['session_cookie_name' => 'session-id']);

        $this->inject($this->sessionMiddleware, 'sessionSettings', [
            'name' => 'session_cookie_name',
            'cookie' => array_merge($this->defaultSessionCookieSettings, $sessionCookieSettings),
        ]);

        $this->mockSessionManager->expects($this->once())->method('initializeCurrentSessionFromCookie')->willReturnCallback(static function (Cookie $cookie) use ($expectedNewCookieValue) {
            self::assertSame($expectedNewCookieValue, (string)$cookie);
            return true;
        });

        $this->sessionMiddleware->process($this->mockHttpRequest, $this->createStub(RequestHandlerInterface::class));
    }

    public static function cookieValueDataProvider(): \Iterator
    {
        yield ['sessionCookieValue' => 123, 'expectedNewCookieValue' => '123'];
        yield ['sessionCookieValue' => '', 'expectedNewCookieValue' => ''];
        yield ['sessionCookieValue' => 'some String', 'expectedNewCookieValue' => 'some String'];
        yield ['sessionCookieValue' => '"leading quote', 'expectedNewCookieValue' => 'leading quote'];
        yield ['sessionCookieValue' => 'trailing quote"', 'expectedNewCookieValue' => 'trailing quote'];
        yield ['sessionCookieValue' => '"quotes"', 'expectedNewCookieValue' => 'quotes'];
        yield ['sessionCookieValue' => '""double quotes"', 'expectedNewCookieValue' => 'double quotes'];
        yield ['sessionCookieValue' => '%22encoded quotes%22', 'expectedNewCookieValue' => 'encoded quotes'];
        // Note: The following test cases merely document the status quo.
        // The cookie values are valid according to https://tools.ietf.org/html/rfc6265#section-4.1.1 but we might want to tweak the behavior in the future
        yield ['sessionCookieValue' => '   whitespace   ', 'expectedNewCookieValue' => '   whitespace   '];
        yield ['sessionCookieValue' => "\t" . 'tabs' . "\t", 'expectedNewCookieValue' => '	tabs	'];
        yield ['sessionCookieValue' => 'semicolon;', 'expectedNewCookieValue' => 'semicolon;'];
        yield ['sessionCookieValue' => '%C3%BCrl%20encoded', 'expectedNewCookieValue' => 'ürl encoded'];
    }

    #[DataProvider('cookieValueDataProvider')]
    #[Test]
    public function valueFromSessionCookieIsCleanedBeforeANewCookieIsCreated($sessionCookieValue, $expectedNewCookieValue): void
    {
        $this->mockHttpRequest->method('getCookieParams')->willReturn([
            'session_cookie_name' => $sessionCookieValue,
        ]);

        $this->mockSessionManager->expects($this->once())->method('initializeCurrentSessionFromCookie');

        $this->sessionMiddleware->process($this->mockHttpRequest, $this->createStub(RequestHandlerInterface::class));
    }
}

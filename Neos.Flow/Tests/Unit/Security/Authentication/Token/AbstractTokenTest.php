<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authentication\Token;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Security\Authentication\EntryPoint\WebRedirect;
use Neos\Flow\Security\Authentication\Token\AbstractToken;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Exception\InvalidAuthenticationStatusException;
use Neos\Flow\Security\RequestPattern\Uri as UriRequestPattern;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for abstract authentication token
 *
 */
final class AbstractTokenTest extends UnitTestCase
{
    /**
     * @var AbstractToken
     */
    protected $token;

    protected function setUp(): void
    {
        $this->token = $this->createPartialMock(AbstractToken::class, ['updateCredentials']);
    }

    #[Test]
    public function authenticationProviderNameCanBeSetAndRetrieved()
    {
        $this->token->setAuthenticationProviderName('My Cool Provider');
        self::assertEquals('My Cool Provider', $this->token->getAuthenticationProviderName());
    }

    #[Test]
    public function authenticationEntryPointCanBeSetAndRetrieved()
    {
        $entryPoint = new WebRedirect();
        $this->token->setAuthenticationEntryPoint($entryPoint);
        self::assertSame($entryPoint, $this->token->getAuthenticationEntryPoint());
    }

    #[Test]
    public function theAuthenticationStatusIsCorrectlyInitialized()
    {
        self::assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function authenticationStatusAndIsAuthenticated(): \Iterator
    {
        yield [TokenInterface::NO_CREDENTIALS_GIVEN, false];
        yield [TokenInterface::AUTHENTICATION_NEEDED, false];
        yield [TokenInterface::WRONG_CREDENTIALS, false];
        yield [TokenInterface::AUTHENTICATION_SUCCESSFUL, true];
    }

    #[DataProvider('authenticationStatusAndIsAuthenticated')]
    #[Test]
    public function isAuthenticatedReturnsTheCorrectValueForAGivenStatus($status, $isAuthenticated)
    {
        $this->token->setAuthenticationStatus($status);
        self::assertEquals($isAuthenticated, $this->token->isAuthenticated());
        $this->token->setAuthenticationStatus($status);
        self::assertEquals($isAuthenticated, $this->token->isAuthenticated());
        $this->token->setAuthenticationStatus($status);
        self::assertEquals($isAuthenticated, $this->token->isAuthenticated());
        $this->token->setAuthenticationStatus($status);
        self::assertEquals($isAuthenticated, $this->token->isAuthenticated());
    }

    #[Test]
    public function setAuthenticationStatusThrowsAnExceptionForAnInvalidStatus()
    {
        $this->expectException(InvalidAuthenticationStatusException::class);
        $this->token->setAuthenticationStatus(-1);
    }

    #[Test]
    public function requestPatternsCanBeSetRetrievedAndChecked()
    {
        self::assertFalse($this->token->hasRequestPatterns());

        $uriRequestPattern = new UriRequestPattern(['uriPattern' => 'http://mydomain.com/some/path/pattern']);
        $this->token->setRequestPatterns([$uriRequestPattern]);

        self::assertTrue($this->token->hasRequestPatterns());
        self::assertEquals([$uriRequestPattern], $this->token->getRequestPatterns());
    }

    #[Test]
    public function setRequestPatternsOnlyAcceptsRequestPatterns()
    {
        $this->expectException(\InvalidArgumentException::class);
        $uriRequestPattern = new UriRequestPattern(['uriPattern' => 'http://mydomain.com/some/path/pattern']);
        $this->token->setRequestPatterns([$uriRequestPattern, 'no valid pattern']);
    }
}

<?php
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

/**
 * Testcase for abstract authentication token
 *
 */
class AbstractTokenTest extends UnitTestCase
{
    /**
     * @var AbstractToken
     */
    protected $token;

    protected function setUp(): void
    {
        $this->token = $this->getMockForAbstractClass(AbstractToken::class);
    }

    /**
     * @test
     */
    public function authenticationProviderNameCanBeSetAndRetrieved()
    {
        $this->token->setAuthenticationProviderName('My Cool Provider');
        self::assertEquals('My Cool Provider', $this->token->getAuthenticationProviderName());
    }

    /**
     * @test
     */
    public function authenticationEntryPointCanBeSetAndRetrieved()
    {
        $entryPoint = new WebRedirect();
        $this->token->setAuthenticationEntryPoint($entryPoint);
        self::assertSame($entryPoint, $this->token->getAuthenticationEntryPoint());
    }

    /**
     * @test
     */
    public function theAuthenticationStatusIsCorrectlyInitialized()
    {
        self::assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
    }

    /**
     * @return array
     */
    public function authenticationStatusAndIsAuthenticated()
    {
        return [
            [TokenInterface::NO_CREDENTIALS_GIVEN, false],
            [TokenInterface::AUTHENTICATION_NEEDED, false],
            [TokenInterface::WRONG_CREDENTIALS, false],
            [TokenInterface::AUTHENTICATION_SUCCESSFUL, true],
        ];
    }

    /**
     * @test
     * @dataProvider authenticationStatusAndIsAuthenticated
     */
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

    /**
     * @test
     */
    public function setAuthenticationStatusThrowsAnExceptionForAnInvalidStatus()
    {
        $this->expectException(InvalidAuthenticationStatusException::class);
        $this->token->setAuthenticationStatus(-1);
    }

    /**
     * @test
     */
    public function requestPatternsCanBeSetRetrievedAndChecked()
    {
        self::assertFalse($this->token->hasRequestPatterns());

        $uriRequestPattern = new UriRequestPattern(['uriPattern' => 'http://mydomain.com/some/path/pattern']);
        $this->token->setRequestPatterns([$uriRequestPattern]);

        self::assertTrue($this->token->hasRequestPatterns());
        self::assertEquals([$uriRequestPattern], $this->token->getRequestPatterns());
    }

    /**
     * @test
     */
    public function setRequestPatternsOnlyAcceptsRequestPatterns()
    {
        $this->expectException(\InvalidArgumentException::class);
        $uriRequestPattern = new UriRequestPattern(['uriPattern' => 'http://mydomain.com/some/path/pattern']);
        $this->token->setRequestPatterns([$uriRequestPattern, 'no valid pattern']);
    }
}

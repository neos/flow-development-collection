<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\Token;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\RequestPattern\Uri as UriRequestPattern;

/**
 * Testcase for abstract authentication token
 *
 */
class AbstractTokenTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Security\Authentication\Token\AbstractToken
     */
    protected $token;

    public function setup()
    {
        $this->token = $this->getMockForAbstractClass(\TYPO3\Flow\Security\Authentication\Token\AbstractToken::class);
    }

    /**
     * @test
     */
    public function authenticationProviderNameCanBeSetAndRetrieved()
    {
        $this->token->setAuthenticationProviderName('My Cool Provider');
        $this->assertEquals('My Cool Provider', $this->token->getAuthenticationProviderName());
    }

    /**
     * @test
     */
    public function authenticationEntryPointCanBeSetAndRetrieved()
    {
        $entryPoint = new \TYPO3\Flow\Security\Authentication\EntryPoint\WebRedirect();
        $this->token->setAuthenticationEntryPoint($entryPoint);
        $this->assertSame($entryPoint, $this->token->getAuthenticationEntryPoint());
    }

    /**
     * @test
     */
    public function theAuthenticationStatusIsCorrectlyInitialized()
    {
        $this->assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
    }

    /**
     * @return array
     */
    public function authenticationStatusAndIsAuthenticated()
    {
        return array(
            array(TokenInterface::NO_CREDENTIALS_GIVEN, false),
            array(TokenInterface::AUTHENTICATION_NEEDED, false),
            array(TokenInterface::WRONG_CREDENTIALS, false),
            array(TokenInterface::AUTHENTICATION_SUCCESSFUL, true),
        );
    }

    /**
     * @test
     * @dataProvider authenticationStatusAndIsAuthenticated
     */
    public function isAuthenticatedReturnsTheCorrectValueForAGivenStatus($status, $isAuthenticated)
    {
        $this->token->setAuthenticationStatus($status);
        $this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
        $this->token->setAuthenticationStatus($status);
        $this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
        $this->token->setAuthenticationStatus($status);
        $this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
        $this->token->setAuthenticationStatus($status);
        $this->assertEquals($isAuthenticated, $this->token->isAuthenticated());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\InvalidAuthenticationStatusException
     */
    public function setAuthenticationStatusThrowsAnExceptionForAnInvalidStatus()
    {
        $this->token->setAuthenticationStatus(-1);
    }

    /**
     * @test
     */
    public function requestPatternsCanBeSetRetrievedAndChecked()
    {
        $this->assertFalse($this->token->hasRequestPatterns());

        $uriRequestPattern = new UriRequestPattern('http://mydomain.com/some/path/pattern');
        $this->token->setRequestPatterns(array($uriRequestPattern));

        $this->assertTrue($this->token->hasRequestPatterns());
        $this->assertEquals(array($uriRequestPattern), $this->token->getRequestPatterns());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setRequestPatternsOnlyAcceptsRequestPatterns()
    {
        $uriRequestPattern = new UriRequestPattern('http://mydomain.com/some/path/pattern');
        $this->token->setRequestPatterns(array($uriRequestPattern, 'no valid pattern'));
    }
}

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

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Token\UsernamePasswordHttpBasic;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;

/**
 * Testcase for username/password HTTP Basic Auth authentication token
 *
 */
class UsernamePasswordHttpBasicTest extends UnitTestCase
{
    /**
     * @var UsernamePasswordHttpBasic
     */
    protected $token;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->token = new UsernamePasswordHttpBasic();
    }

    /**
     * @test
     */
    public function credentialsAreSetCorrectlyFromRequestHeadersArguments()
    {
        $serverEnvironment = [
            'PHP_AUTH_USER' => 'robert',
            'PHP_AUTH_PW' => 'mysecretpassword, containing a : colon ;-)'
        ];

        $httpRequest = (new ServerRequestFactory(new UriFactory()))->createServerRequest('GET', new Uri('http://foo.com'), $serverEnvironment);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects(self::atLeastOnce())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $this->token->updateCredentials($mockActionRequest);

        $expectedCredentials = ['username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)'];
        self::assertEquals($expectedCredentials, $this->token->getCredentials());
        self::assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
    }

    /**
     * @test
     */
    public function credentialsAreSetCorrectlyForCGI()
    {
        $expectedCredentials = ['username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)'];

        $serverEnvironment = [
            'REDIRECT_REMOTE_AUTHORIZATION' => 'Basic ' . base64_encode($expectedCredentials['username'] . ':' . $expectedCredentials['password'])
        ];

        $httpRequest = (new ServerRequestFactory(new UriFactory()))->createServerRequest('GET', new Uri('http://foo.com'), $serverEnvironment);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects(self::atLeastOnce())->method('getHttpRequest')->will(self::returnValue($httpRequest));
        $this->token->updateCredentials($mockActionRequest);

        self::assertEquals($expectedCredentials, $this->token->getCredentials());
        self::assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
    }

    /**
     * @test
     */
    public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNoCredentialsArrived()
    {
        $httpRequest = new ServerRequest('GET', new Uri('http://foo.com'));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects(self::atLeastOnce())->method('getHttpRequest')->will(self::returnValue($httpRequest));
        $this->token->updateCredentials($mockActionRequest);

        self::assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
    }
}

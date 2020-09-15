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

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Token\UsernamePasswordHttpBasic;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Tests\UnitTestCase;

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
    public function setUp()
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

        $httpRequest = Request::create(new Uri('http://foo.com'), 'GET', [], [], $serverEnvironment);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $this->token->updateCredentials($mockActionRequest);

        $expectedCredentials = ['username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)'];
        $this->assertEquals($expectedCredentials, $this->token->getCredentials());
        $this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
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

        $httpRequest = Request::create(new Uri('http://foo.com'), 'GET', [], [], $serverEnvironment);
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($httpRequest));
        $this->token->updateCredentials($mockActionRequest);

        $this->assertEquals($expectedCredentials, $this->token->getCredentials());
        $this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
    }

    /**
     * @test
     */
    public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNoCredentialsArrived()
    {
        $httpRequest = Request::create(new Uri('http://foo.com'));
        $mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($httpRequest));
        $this->token->updateCredentials($mockActionRequest);

        $this->assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
    }
}

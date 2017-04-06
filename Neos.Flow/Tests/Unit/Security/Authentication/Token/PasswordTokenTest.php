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

use Neos\Flow\Http;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authentication\Token\PasswordToken;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for password authentication token
 */
class PasswordTokenTest extends UnitTestCase
{
    /**
     * @var PasswordToken
     */
    protected $token;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * @var Http\Request
     */
    protected $mockHttpRequest;

    /**
     * Set up this test case
     */
    public function setUp()
    {
        $this->token = new PasswordToken();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
    }

    /**
     * @test
     */
    public function credentialsAreSetCorrectlyFromPostArguments()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->will($this->returnValue($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        $expectedCredentials = ['password' => 'verysecurepassword'];
        $this->assertEquals($expectedCredentials, $this->token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
    }

    /**
     * @test
     */
    public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->will($this->returnValue($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        $this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
    }

    /**
     * @test
     */
    public function updateCredentialsIgnoresAnythingOtherThanPostRequests()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->will($this->returnValue($arguments));

        $this->token->updateCredentials($this->mockActionRequest);
        $this->assertEquals(['password' => 'verysecurepassword'], $this->token->getCredentials());

        $secondToken = new PasswordToken();
        $secondMockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $secondMockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
        $secondMockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($secondMockHttpRequest));
        $secondMockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('GET'));
        $secondToken->updateCredentials($secondMockActionRequest);
        $this->assertEquals(['password' => ''], $secondToken->getCredentials());
    }
}

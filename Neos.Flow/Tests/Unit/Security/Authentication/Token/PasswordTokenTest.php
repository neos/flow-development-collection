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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authentication\Token\PasswordToken;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var ServerRequestInterface
     */
    protected $mockHttpRequest;

    /**
     * Set up this test case
     */
    protected function setUp(): void
    {
        $this->token = new PasswordToken();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->mockHttpRequest));
    }

    /**
     * @test
     */
    public function credentialsAreSetCorrectlyFromPostArguments()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('POST'));
        $this->mockActionRequest->expects(self::atLeastOnce())->method('getInternalArguments')->will(self::returnValue($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        $expectedCredentials = ['password' => 'verysecurepassword'];
        self::assertEquals($expectedCredentials, $this->token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
    }

    /**
     * @test
     */
    public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('POST'));
        $this->mockActionRequest->expects(self::atLeastOnce())->method('getInternalArguments')->will(self::returnValue($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        self::assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
    }

    /**
     * @test
     */
    public function updateCredentialsIgnoresAnythingOtherThanPostRequests()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('POST'));
        $this->mockActionRequest->expects(self::atLeastOnce())->method('getInternalArguments')->will(self::returnValue($arguments));

        $this->token->updateCredentials($this->mockActionRequest);
        self::assertEquals(['password' => 'verysecurepassword'], $this->token->getCredentials());

        $secondToken = new PasswordToken();
        $secondMockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $secondMockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $secondMockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($secondMockHttpRequest));
        $secondMockHttpRequest->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('GET'));
        $secondToken->updateCredentials($secondMockActionRequest);
        self::assertEquals(['password' => ''], $secondToken->getCredentials());
    }
}

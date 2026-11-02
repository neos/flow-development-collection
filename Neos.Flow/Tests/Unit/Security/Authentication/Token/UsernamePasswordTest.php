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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Authentication\Token\UsernamePassword;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for username/password authentication token
 *
 */
final class UsernamePasswordTest extends UnitTestCase
{
    /**
     * @var UsernamePassword
     */
    protected $token;

    /**
     * @var ActionRequest|MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * Set up this test case
     */
    protected function setUp(): void
    {
        $this->token = new UsernamePassword();

        $this->mockActionRequest = $this->createMock(ActionRequest::class);

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($this->mockHttpRequest));
    }

    #[Test]
    public function credentialsAreSetCorrectlyFromPostArguments()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'johndoe';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->willReturn(($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        $expectedCredentials = ['username' => 'johndoe', 'password' => 'verysecurepassword'];
        self::assertEquals($expectedCredentials, $this->token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
    }

    #[Test]
    public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'Neos.Flow';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->willReturn(($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        self::assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
    }

    #[Test]
    public function updateCredentialsIgnoresAnythingOtherThanPostRequests()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'Neos.Flow';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->willReturn(($arguments));

        $this->token->updateCredentials($this->mockActionRequest);
        self::assertEquals(['username' => 'Neos.Flow', 'password' => 'verysecurepassword'], $this->token->getCredentials());

        $secondToken = new UsernamePassword();
        $secondMockActionRequest = $this->createMock(ActionRequest::class);

        /** @var ActionRequest|MockObject $secondMockActionRequest */
        $secondMockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $secondMockActionRequest->method('getHttpRequest')->willReturn(($secondMockHttpRequest));
        $secondMockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn(('GET'));
        $secondToken->updateCredentials($secondMockActionRequest);
        self::assertEquals(['username' => '', 'password' => ''], $secondToken->getCredentials());
    }

    #[Test]
    public function tokenCanBeCastToString()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'Neos.Flow';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->willReturn(($arguments));

        $this->token->updateCredentials($this->mockActionRequest);

        self::assertSame('Username: "Neos.Flow"', (string)$this->token);
    }
}

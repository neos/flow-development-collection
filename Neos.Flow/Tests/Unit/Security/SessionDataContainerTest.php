<?php
namespace Neos\Flow\Tests\Unit\Security;

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
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenAndProviderFactory;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Security\SessionDataContainer;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security\Policy\Role;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the SessionDataContainer
 */
class SessionDataContainerTest extends UnitTestCase
{

    /**
     * @var SessionDataContainer
     */
    private $sessionDataContainer;

    public function setUp(): void
    {
        $this->sessionDataContainer = new SessionDataContainer();
    }

    /**
     * @test
     */
    public function resetSetsDefaultValues(): void
    {
        $mockCsrfProtectionTokens = [
            'mock' => true,
        ];

        $this->sessionDataContainer->setCsrfProtectionTokens($mockCsrfProtectionTokens);

        /** @var RequestInterface $mockRequest */
        $mockRequest = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this->sessionDataContainer->setInterceptedRequest($mockRequest);

        $mockSecurityTokens = [
            'someProvider' => $this->getMockBuilder(TokenInterface::class)->getMock()
        ];
        $this->sessionDataContainer->setSecurityTokens($mockSecurityTokens);

        $this->sessionDataContainer->reset();

        $this->assertSame([], $this->sessionDataContainer->getCsrfProtectionTokens());
        $this->assertNull($this->sessionDataContainer->getInterceptedRequest());
        $this->assertSame([], $this->sessionDataContainer->getSecurityTokens());
    }

    /**
     * @test
     */
    public function setSecurityTokensThrowsExceptionWhenTryingToAddSessionlessTokens(): void
    {
        $mockSecurityTokens = [
            'someProvider' => $this->getMockBuilder([TokenInterface::class, SessionlessTokenInterface::class])->getMock()
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->sessionDataContainer->setSecurityTokens($mockSecurityTokens);
    }
}

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
use Neos\Flow\Security\Authentication\Token\TestingToken;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\SessionDataContainer;
use Neos\Flow\Tests\UnitTestCase;

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

        /** @var ActionRequest $mockRequest */
        $mockRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->sessionDataContainer->setInterceptedRequest($mockRequest);

        $mockSecurityTokens = [
            'someProvider' => $this->getMockBuilder(TokenInterface::class)->getMock()
        ];
        $this->sessionDataContainer->setSecurityTokens($mockSecurityTokens);

        $this->sessionDataContainer->reset();

        self::assertSame([], $this->sessionDataContainer->getCsrfProtectionTokens());
        self::assertNull($this->sessionDataContainer->getInterceptedRequest());
        self::assertSame([], $this->sessionDataContainer->getSecurityTokens());
    }

    /**
     * @test
     */
    public function setSecurityTokensThrowsExceptionWhenTryingToAddSessionlessTokens(): void
    {
        $mockSecurityTokens = [
            'someProvider' => $this->getMockBuilder(TestingToken::class)->getMock()
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->sessionDataContainer->setSecurityTokens($mockSecurityTokens);
    }
}

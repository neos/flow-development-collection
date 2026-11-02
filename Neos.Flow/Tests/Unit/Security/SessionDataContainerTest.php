<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the SessionDataContainer
 */
final class SessionDataContainerTest extends UnitTestCase
{
    /**
     * @var SessionDataContainer
     */
    private $sessionDataContainer;

    public function setUp(): void
    {
        $this->sessionDataContainer = new SessionDataContainer();
    }

    #[Test]
    public function resetSetsDefaultValues(): void
    {
        $mockCsrfProtectionTokens = [
            'mock' => true,
        ];

        $this->sessionDataContainer->setCsrfProtectionTokens($mockCsrfProtectionTokens);

        /** @var ActionRequest $mockRequest */
        $mockRequest = $this->createStub(ActionRequest::class);
        $this->sessionDataContainer->setInterceptedRequest($mockRequest);

        $mockSecurityTokens = [
            'someProvider' => $this->createStub(TokenInterface::class)
        ];
        $this->sessionDataContainer->setSecurityTokens($mockSecurityTokens);

        $this->sessionDataContainer->reset();

        self::assertSame([], $this->sessionDataContainer->getCsrfProtectionTokens());
        self::assertNull($this->sessionDataContainer->getInterceptedRequest());
        self::assertSame([], $this->sessionDataContainer->getSecurityTokens());
    }

    #[Test]
    public function setSecurityTokensThrowsExceptionWhenTryingToAddSessionlessTokens(): void
    {
        $mockSecurityTokens = [
            'someProvider' => $this->createStub(TestingToken::class)
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->sessionDataContainer->setSecurityTokens($mockSecurityTokens);
    }
}

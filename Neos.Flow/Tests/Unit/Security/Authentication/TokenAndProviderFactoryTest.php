<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authentication;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Security\Authentication\AuthenticationProviderResolver;
use Neos\Flow\Security\Authentication\AuthenticationTokenResolver;
use Neos\Flow\Security\Authentication\TokenAndProviderFactory;
use Neos\Flow\Security\Exception\InvalidAuthenticationProviderException;
use Neos\Flow\Security\RequestPatternResolver;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for the default token and provider factory
 */
final class TokenAndProviderFactoryTest extends UnitTestCase
{
    #[Test]
    public function noTokensAndProvidersAreBuiltIfTheConfigurationArrayIsEmpty()
    {
        $mockProviderResolver = $this->createStub(AuthenticationProviderResolver::class);
        $mockRequestPatternResolver = $this->createStub(RequestPatternResolver::class);
        $mockTokenResolver = $this->createStub(AuthenticationTokenResolver::class);

        $tokenAndProviderFactory = new TokenAndProviderFactory($mockProviderResolver, $mockRequestPatternResolver, $mockTokenResolver);

        self::assertSame([], $tokenAndProviderFactory->getProviders(), 'The array of providers should be empty.');
        self::assertSame([], $tokenAndProviderFactory->getTokens(), 'The array of tokens should be empty.');
    }

    #[Test]
    public function anExceptionIsThrownIfTheConfiguredProviderDoesNotExist()
    {
        $this->expectException(InvalidAuthenticationProviderException::class);
        $providerConfiguration = [
            'NotExistingProvider' => [
                'providerClass' => 'NotExistingProviderClass'
            ],
        ];

        $mockProviderResolver = $this->createStub(AuthenticationProviderResolver::class);
        $mockRequestPatternResolver = $this->createStub(RequestPatternResolver::class);
        $mockTokenResolver = $this->createStub(AuthenticationTokenResolver::class);

        $tokenAndProviderFactory = new TokenAndProviderFactory($mockProviderResolver, $mockRequestPatternResolver, $mockTokenResolver);
        $tokenAndProviderFactory->injectSettings(['security' => ['authentication' => ['providers' => $providerConfiguration]]]);

        $tokenAndProviderFactory->getProviders();
    }
}

<?php
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

/**
 * Test for the default token and provider factory
 */
class TokenAndProviderFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function noTokensAndProvidersAreBuiltIfTheConfigurationArrayIsEmpty()
    {
        $mockProviderResolver = $this->getMockBuilder(AuthenticationProviderResolver::class)->disableOriginalConstructor()->getMock();
        $mockRequestPatternResolver = $this->getMockBuilder(RequestPatternResolver::class)->disableOriginalConstructor()->getMock();
        $mockTokenResolver = $this->getMockBuilder(AuthenticationTokenResolver::class)->disableOriginalConstructor()->getMock();

        $tokenAndProviderFactory = new TokenAndProviderFactory($mockProviderResolver, $mockRequestPatternResolver, $mockTokenResolver);

        self::assertEquals([], $tokenAndProviderFactory->getProviders(), 'The array of providers should be empty.');
        self::assertEquals([], $tokenAndProviderFactory->getTokens(), 'The array of tokens should be empty.');
    }

    /**
     * @test
     */
    public function anExceptionIsThrownIfTheConfiguredProviderDoesNotExist()
    {
        $this->expectException(InvalidAuthenticationProviderException::class);
        $providerConfiguration = [
            'NotExistingProvider' => [
                'providerClass' => 'NotExistingProviderClass'
            ],
        ];

        $mockProviderResolver = $this->getMockBuilder(AuthenticationProviderResolver::class)->disableOriginalConstructor()->getMock();
        $mockRequestPatternResolver = $this->getMockBuilder(RequestPatternResolver::class)->disableOriginalConstructor()->getMock();
        $mockTokenResolver = $this->getMockBuilder(AuthenticationTokenResolver::class)->disableOriginalConstructor()->getMock();

        $tokenAndProviderFactory = new TokenAndProviderFactory($mockProviderResolver, $mockRequestPatternResolver, $mockTokenResolver);
        $tokenAndProviderFactory->injectSettings(['security' => ['authentication' => ['providers' => $providerConfiguration]]]);

        $tokenAndProviderFactory->getProviders();
    }
}

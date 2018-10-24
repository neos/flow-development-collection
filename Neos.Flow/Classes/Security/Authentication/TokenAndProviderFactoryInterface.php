<?php
namespace Neos\Flow\Security\Authentication;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Factory contract for providers and their tokens.
 * As both are closely related they are created in the same factory.
 */
interface TokenAndProviderFactoryInterface
{
    /**
     * @return TokenInterface[]
     */
    public function getTokens(): array;

    /**
     * @return AuthenticationProviderInterface[]
     */
    public function getProviders(): array;
}

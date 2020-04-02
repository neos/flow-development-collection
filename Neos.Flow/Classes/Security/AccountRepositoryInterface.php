<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Flow\Security;

interface AccountRepositoryInterface
{
    public function findByAccountIdentifierAndAuthenticationProviderName(string $accountIdentifier, string $authenticationProviderName): ?AccountInterface;

    public function findActiveByAccountIdentifierAndAuthenticationProviderName(string $accountIdentifier, string $authenticationProviderName): ?AccountInterface;
}

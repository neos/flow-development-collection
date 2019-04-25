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
 * Contract for an authentication provider used by the AuthenticationProviderManager.
 * Has to add a TokenInterface to the security context, which contains.
 */
interface AuthenticationProviderInterface
{
    /**
     * Constructs an instance with the given name and options.
     *
     * @param string $name
     * @param array $options
     * @return self
     */
    public static function create(string $name, array $options);

    /**
     * Returns true if the given token can be authenticated by this provider
     *
     * @param TokenInterface $token The token that should be authenticated
     * @return boolean true if the given token class can be authenticated by this provider
     */
    public function canAuthenticate(TokenInterface $token);

    /**
     * Returns the classnames of the tokens this provider is responsible for.
     *
     * @return array The classname of the token this provider is responsible for
     */
    public function getTokenClassNames();

    /**
     * Tries to authenticate the given token. Sets isAuthenticated to true if authentication succeeded.
     *
     * @param TokenInterface $authenticationToken The token to be authenticated
     * @return void
     */
    public function authenticate(TokenInterface $authenticationToken);
}

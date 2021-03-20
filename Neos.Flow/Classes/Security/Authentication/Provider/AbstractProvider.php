<?php
namespace Neos\Flow\Security\Authentication\Provider;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authentication\AuthenticationProviderInterface;
use Neos\Flow\Security\Authentication\TokenInterface;

/**
 * An abstract authentication provider.
 */
abstract class AbstractProvider implements AuthenticationProviderInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Factory method
     *
     * @param string $name
     * @param array $options
     * @return AuthenticationProviderInterface
     * @api
     */
    public static function create(string $name, array $options)
    {
        return new static($name, $options);
    }

    /**
     * Protected constructor, see create method
     *
     * @param string $name The name of this authentication provider
     * @param array $options Additional configuration options
     * @see create
     */
    protected function __construct($name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Returns true if the given token can be authenticated by this provider
     *
     * @param TokenInterface $authenticationToken The token that should be authenticated
     * @return boolean true if the given token class can be authenticated by this provider
     */
    public function canAuthenticate(TokenInterface $authenticationToken)
    {
        return ($authenticationToken->getAuthenticationProviderName() === $this->name);
    }
}

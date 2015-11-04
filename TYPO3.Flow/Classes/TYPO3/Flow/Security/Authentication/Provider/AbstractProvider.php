<?php
namespace TYPO3\Flow\Security\Authentication\Provider;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * An abstract authentication provider.
 */
abstract class AbstractProvider implements \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Constructor
     *
     * @param string $name The name of this authentication provider
     * @param array $options Additional configuration options
     */
    public function __construct($name, array $options = array())
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Returns TRUE if the given token can be authenticated by this provider
     *
     * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token that should be authenticated
     * @return boolean TRUE if the given token class can be authenticated by this provider
     */
    public function canAuthenticate(\TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken)
    {
        if ($authenticationToken->getAuthenticationProviderName() === $this->name) {
            return true;
        } else {
            return false;
        }
    }
}

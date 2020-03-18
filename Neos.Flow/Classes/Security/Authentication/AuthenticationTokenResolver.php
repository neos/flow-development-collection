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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Exception\NoAuthenticationTokenFoundException;

/**
 * The authentication token resolver. It resolves the class name of a authentication token based on names.
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationTokenResolver
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager The object manager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Resolves the class name of an authentication token. If a valid token class name is given, it is just returned.
     *
     * @param string $tokenName The (short) name of the token
     * @return string The object name of the authentication token
     * @throws NoAuthenticationTokenFoundException
     */
    public function resolveTokenClass(string $tokenName): string
    {
        $className = $this->objectManager->getClassNameByObjectName($tokenName);
        if ($className !== false) {
            return $className;
        }

        $className = $this->objectManager->getClassNameByObjectName('Neos\Flow\Security\Authentication\Token\\' . $tokenName);
        if ($className !== false) {
            return $className;
        }

        throw new NoAuthenticationTokenFoundException('An authentication token with the name "' . $tokenName . '" could not be resolved.', 1217154134);
    }
}

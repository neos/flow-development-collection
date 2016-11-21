<?php
namespace TYPO3\Flow\Security\Authentication;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Exception\NoAuthenticationProviderFoundException;

/**
 * The authentication provider resolver. It resolves the class name of a authentication provider based on names.
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationProviderResolver
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
     * Resolves the class name of an authentication provider. If a valid provider class name is given, it is just returned.
     *
     * @param string $providerName The (short) name of the provider
     * @return string The object name of the authentication provider
     * @throws NoAuthenticationProviderFoundException
     */
    public function resolveProviderClass($providerName)
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($providerName);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\Flow\Security\Authentication\Provider\\' . $providerName);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        throw new NoAuthenticationProviderFoundException('An authentication provider with the name "' . $providerName . '" could not be resolved.', 1217154134);
    }
}

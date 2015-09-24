<?php
namespace TYPO3\Flow\Security\Authentication;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The authentication provider resolver. It resolves the class name of a authentication provider based on names.
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationProviderResolver
{
    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor.
     *
     * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager The object manager
     */
    public function __construct(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Resolves the class name of an authentication provider. If a valid provider class name is given, it is just returned.
     *
     * @param string $providerName The (short) name of the provider
     * @return string The object name of the authentication provider
     * @throws \TYPO3\Flow\Security\Exception\NoAuthenticationProviderFoundException
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

        throw new \TYPO3\Flow\Security\Exception\NoAuthenticationProviderFoundException('An authentication provider with the name "' . $providerName . '" could not be resolved.', 1217154134);
    }
}

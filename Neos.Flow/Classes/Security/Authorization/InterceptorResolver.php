<?php
namespace Neos\Flow\Security\Authorization;

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
use Neos\Flow\Security\Exception\NoInterceptorFoundException;

/**
 * The security interceptor resolver. It resolves the class name of a security interceptor based on names.
 *
 * @Flow\Scope("singleton")
 */
class InterceptorResolver
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
     * Resolves the class name of a security interceptor. If a valid interceptor class name is given, it is just returned.
     *
     * @param string $name The (short) name of the interceptor
     * @return string The class name of the security interceptor, NULL if no class was found.
     * @throws NoInterceptorFoundException
     */
    public function resolveInterceptorClass($name)
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('Neos\Flow\Security\Authorization\Interceptor\\' . $name);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        throw new NoInterceptorFoundException('A security interceptor with the name: "' . $name . '" could not be resolved.', 1217154134);
    }
}

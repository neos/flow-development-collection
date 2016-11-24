<?php
namespace Neos\Flow\Security;

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

/**
 * The request pattern resolver. It resolves the class name of a request pattern based on names.
 *
 * @Flow\Scope("singleton")
 */
class RequestPatternResolver
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
     * Resolves the class name of a request pattern. If a valid request pattern class name is given, it is just returned.
     *
     * @param string $name The (short) name of the pattern
     * @return string The class name of the request pattern, NULL if no class was found.
     * @throws Exception\NoRequestPatternFoundException
     */
    public function resolveRequestPatternClass($name)
    {
        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        $resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('Neos\Flow\Security\RequestPattern\\' . $name);
        if ($resolvedObjectName !== false) {
            return $resolvedObjectName;
        }

        throw new Exception\NoRequestPatternFoundException('A request pattern with the name: "' . $name . '" could not be resolved.', 1217154134);
    }
}

<?php
namespace TYPO3\FLOW3\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The security interceptor resolver. It resolves the class name of a security interceptor based on names.
 *
 * @FLOW3\Scope("singleton")
 */
class InterceptorResolver {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager The object manager
	 */
	public function __construct(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Resolves the class name of a security interceptor. If a valid interceptor class name is given, it is just returned.
	 *
	 * @param string $name The (short) name of the interceptor
	 * @return string The class name of the security interceptor, NULL if no class was found.
	 * @throws \TYPO3\FLOW3\Security\Exception\NoInterceptorFoundException
	 */
	public function resolveInterceptorClass($name) {
		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
		if ($resolvedObjectName !== FALSE) return $resolvedObjectName;

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\FLOW3\Security\Authorization\Interceptor\\' . $name);
		if ($resolvedObjectName !== FALSE) return $resolvedObjectName;

		throw new \TYPO3\FLOW3\Security\Exception\NoInterceptorFoundException('A security interceptor with the name: "' . $name . '" could not be resolved.', 1217154134);
	}
}
?>
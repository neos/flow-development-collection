<?php
namespace TYPO3\Flow\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The request pattern resolver. It resolves the class name of a request pattern based on names.
 *
 * @Flow\Scope("singleton")
 */
class RequestPatternResolver {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager The object manager
	 */
	public function __construct(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Resolves the class name of a request pattern. If a valid request pattern class name is given, it is just returned.
	 *
	 * @param string $name The (short) name of the pattern
	 * @return string The class name of the request pattern, NULL if no class was found.
	 * @throws \TYPO3\Flow\Security\Exception\NoRequestPatternFoundException
	 */
	public function resolveRequestPatternClass($name) {
		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('TYPO3\Flow\Security\RequestPattern\\' . $name);
		if ($resolvedObjectName !== FALSE) {
			return $resolvedObjectName;
		}

		throw new \TYPO3\Flow\Security\Exception\NoRequestPatternFoundException('A request pattern with the name: "' . $name . '" could not be resolved.', 1217154134);
	}
}
?>
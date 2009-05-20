<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * The request pattern resolver. It resolves the class name of a request pattern based on names.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestPatternResolver {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface The object manager
	 */
	protected $objectManager;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Resolves the class name of a request pattern. If a valid request pattern class name is given, it is just returned.
	 *
	 * @param string $name The (short) name of the pattern
	 * @return string The class name of the request pattern, NULL if no class was found.
	 * @throws \F3\FLOW3\Security\Exception\NoRequestPatternFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function resolveRequestPatternClass($name) {
		$resolvedClassName = '';

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
		if ($resolvedObjectName !== FALSE) return $resolvedObjectName;

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('F3\FLOW3\Security\RequestPattern\\' . $name);
		if ($resolvedObjectName !== FALSE) return $resolvedObjectName;

		throw new \F3\FLOW3\Security\Exception\NoRequestPatternFound('A request pattern with the name: "' . $name . '" could not be resolved.', 1217154134);
	}
}
?>
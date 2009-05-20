<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * @version $Id: RequestPatternResolver.php 1811 2009-01-28 12:04:49Z robert $
 */

/**
 * The authentication entry point resolver. It resolves the class name of an
 * authentication entry point based on names.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id: RequestPatternResolver.php 1811 2009-01-28 12:04:49Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class EntryPointResolver {

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
	 * Resolves the class name of an entry point. If a valid entry point class name is given, it is just returned.
	 *
	 * @param string $name The (short) name of the entry point
	 * @return string The class name of the entry point, NULL if no class was found.
	 * @throws \F3\FLOW3\Security\Exception\NoEntryPointFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @internal
	 */
	public function resolveEntryPointClass($name) {
		$resolvedClassName = '';

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName($name);
		if ($resolvedObjectName !== FALSE) return $resolvedObjectName;

		$resolvedObjectName = $this->objectManager->getCaseSensitiveObjectName('F3\FLOW3\Security\Authentication\EntryPoint\\' . $name);
		if ($resolvedObjectName !== FALSE) return $resolvedObjectName;

		throw new \F3\FLOW3\Security\Exception\NoEntryPointFound('An entry point with the name: "' . $name . '" could not be resolved.', 1236767282);
	}
}
?>
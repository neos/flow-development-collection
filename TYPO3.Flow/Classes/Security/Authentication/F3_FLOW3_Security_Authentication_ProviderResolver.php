<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 */

/**
 * The authentication provider resolver. It resolves the class name of a authentication provider based on names.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_ProviderResolver {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface The component manager
	 */
	protected $componentManager;

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager The component manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Resolves the class name of an authentication provider. If a valid provider class name is given, it is just returned.
	 *
	 * @param string $name The (short) name of the provider
	 * @return string The class name of the authentication provider
	 * @throws F3_FLOW3_Security_Exception_NoAuthenticationProviderFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderClass($name) {
		$resolvedClassName = '';

		$nameIsClassName = $this->componentManager->getCaseSensitiveComponentName($name);
		if ($nameIsClassName) $resolvedClassName = $nameIsClassName;

		$extendedNameIsClassName = $this->componentManager->getCaseSensitiveComponentName('F3_FLOW3_Security_Authentication_Provider_' . $name);
		if ($extendedNameIsClassName) $resolvedClassName = $extendedNameIsClassName;

		if ($resolvedClassName != '') return $resolvedClassName;

		throw new F3_FLOW3_Security_Exception_NoAuthenticationProviderFound('An authentication provider with the name: "' . $name . '" could not be resolved.', 1217154134);
	}
}
?>
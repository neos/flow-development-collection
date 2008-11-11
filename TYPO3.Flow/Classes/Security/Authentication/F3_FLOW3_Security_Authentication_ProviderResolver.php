<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authentication;

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
 * @version $Id$
 */

/**
 * The authentication provider resolver. It resolves the class name of a authentication provider based on names.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ProviderResolver {

	/**
	 * @var F3::FLOW3::Object::ManagerInterface The object manager
	 */
	protected $objectManager;

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Object::ManagerInterface $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Object::ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Resolves the class name of an authentication provider. If a valid provider class name is given, it is just returned.
	 *
	 * @param string $name The (short) name of the provider
	 * @return string The class name of the authentication provider
	 * @throws F3::FLOW3::Security::Exception::NoAuthenticationProviderFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderClass($name) {
		$resolvedClassName = '';

		$nameIsClassName = $this->objectManager->getCaseSensitiveObjectName($name);
		if ($nameIsClassName) $resolvedClassName = $nameIsClassName;

		$extendedNameIsClassName = $this->objectManager->getCaseSensitiveObjectName('F3::FLOW3::Security::Authentication::Provider::' . $name);
		if ($extendedNameIsClassName) $resolvedClassName = $extendedNameIsClassName;

		if ($resolvedClassName != '') return $resolvedClassName;

		throw new F3::FLOW3::Security::Exception::NoAuthenticationProviderFound('An authentication provider with the name: "' . $name . '" could not be resolved.', 1217154134);
	}
}
?>
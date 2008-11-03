<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Component;

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
 * @subpackage Component
 * @version $Id$
 */

/**
 * Contract for a Component Factory
 *
 * @package FLOW3
 * @subpackage Component
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface FactoryInterface {

	/**
	 * Creates a fresh instance of the component specified by $componentName.
	 *
	 * @param string $componentName The name of the component to return an instance of
	 * @return object The component instance
	 * @throws InvalidArgumentException if $componentName is not a string
	 * @throws F3::FLOW3::Component::Exception::UnknownComponent if a component with the given name does not exist
	 * @throws F3::FLOW3::Component::Exception::WrongScope if the specified component is not configured as Prototype
	 */
	public function create($componentName);

}
?>
<?php
declare(encoding = 'utf-8');

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
 * Interface for a component configuration source
 *
 * @package    FLOW3
 * @subpackage Package
 * @version    $Id:T3_FLOW3_Package_ComponentsConfigurationSourceInterface.php 203 2007-03-30 13:17:37Z robert $
 * @author     Robert Lemke <robert@typo3.org>
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_Package_ComponentsConfigurationSourceInterface {

	/**
	 * Returns an array (indexed by component names) of component configuration
	 * objects for the classes of the given package. If no configuration was
	 * found or defined, an empty array is returned.
	 *
	 * @param  T3_FLOW3_Package_PackageInterface $package: The package to return the components configuration for
	 * @param  array $parsedComponentConfigurations: An array of already existing component configurations (if any)
	 * @return array Array of component names and T3_FLOW3_Component_Configuration
	 */
	public function getComponentConfigurations(T3_FLOW3_Package_PackageInterface $package, $parsedComponentConfigurations);
}
?>
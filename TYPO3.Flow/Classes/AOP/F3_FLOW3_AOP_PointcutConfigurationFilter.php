<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id: F3_FLOW3_AOP_PointcutClassTaggedWithFilter.php 1599 2008-12-10 14:39:10Z k-fish $
 */

/**
 * A configuration filter which fires on configuration options set to TRUE.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilter.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutConfigurationFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

	/**
	 * The value of the specified configuration option
	 * @var boolean
	 */
	protected $configurationOption = FALSE;

	/**
	 * The constructor - initializes the configuration filter with the path to a configuration option
	 *
	 * @param string $configurationOptionPath The path to the configuration option
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Configuration\Manager $configurationManager, $configurationOptionPath) {
		$this->configurationOption = $configurationManager->getSettings('FLOW3');

		$configurationKeys = split(': ', $configurationOptionPath);
		foreach ($configurationKeys as $currentKey) {
			if (!isset($this->configurationOption[$currentKey])) {
				$this->configurationOption = FALSE;
				break;
			}
			$this->configurationOption = $this->configurationOption[$currentKey];
		}
	}

	/**
	 * Checks if the specified configuration option is set to TRUE or FALSE
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class Not needed in this filter
	 * @param \F3\FLOW3\Reflection\ClassReflectionMethod $method Not needed in this filter
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the option is set to TRUE, otherwise FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		return (is_bool($this->configurationOption) ? $this->configurationOption : FALSE);
	}
}

?>
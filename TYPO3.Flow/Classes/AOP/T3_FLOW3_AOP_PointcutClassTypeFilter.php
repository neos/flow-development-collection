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
 * A class type filter which fires on class types defined by a regular expression
 * 
 * @package		FLOW3
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_PointcutClassTypeFilter.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_PointcutClassTypeFilter implements T3_FLOW3_AOP_PointcutFilterInterface {

	/**
	 * @var string A regular expression to match class types
	 */
	protected $classTypeFilterExpression;
	
	/**
	 * The constructor - initializes the class type filter with the class type filter expression
	 *
	 * @param  string		$classTypeFilterExpression: A regular expression which defines which class types should match
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($classTypeFilterExpression) {
		$this->classTypeFilterExpression = $classTypeFilterExpression;
	}
	
	/**
	 * Checks if the specified class matches with the class type filter pattern
	 *
	 * @param  ReflectionClass		$class: The class to check against
	 * @param  ReflectionMethod		$method: The method - not used here
	 * @param  mixed				$pointcutQueryIdentifier: Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean				TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(ReflectionClass $class, ReflectionMethod $method, $pointcutQueryIdentifier) {
		$matches = FALSE;
		foreach ($class->getInterfaceNames() as $interfaceName) {
			$matchResult =  @preg_match('/^' . $this->classTypeFilterExpression . '$/', $interfaceName);
			if ($matchResult === FALSE) {
				throw new RuntimeException('Error in regular expression "' . $this->classTypeFilterExpression . '" in pointcut class type filter', 1172483343);
			}
			if ($matchResult === 1) {
				$matches = TRUE;
			}
		}
		return ($matches);
	}
}

?>
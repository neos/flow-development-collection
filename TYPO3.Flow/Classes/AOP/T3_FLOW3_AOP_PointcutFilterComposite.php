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
 * This composite allows to check for match against a row pointcut filters
 * by only one method call. All registered filters will be invoked and if one filter
 * doesn't match, the overall result is "no".
 * 
 * @package		FLOW3
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_PointcutFilterComposite.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see			T3_FLOW3_AOP_PointcutExpressionParser, T3_FLOW3_AOP_PointcutClassFilter, T3_FLOW3_AOP_PointcutMethodFilter
 */
class T3_FLOW3_AOP_PointcutFilterComposite implements T3_FLOW3_AOP_PointcutFilterInterface {
	
	/**
	 * @var array An array of T3_FLOW3_AOP_Pointcut*Filter objects
	 */
	protected $filters = array();
	
	/**
	 * Checks if the specified class and method match the registered class-
	 * and method filter patterns.
	 *
	 * @param  ReflectionClass		$class: The class to check against
	 * @param  ReflectionMethod		$method: The method to check against
	 * @param  mixed				$pointcutQueryIdentifier: Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean				TRUE if class and method match the pattern, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */	
	public function matches(ReflectionClass $class, ReflectionMethod $method, $pointcutQueryIdentifier) {
		$matches = TRUE;
		foreach ($this->filters as $operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;
			switch ($operator) {
				case '&&' : 
					$matches = $matches	&& $filter->matches($class, $method, $pointcutQueryIdentifier);
				break;
				case '&&!' : 
					$matches = $matches	&& (!$filter->matches($class, $method, $pointcutQueryIdentifier));
				break;
				case '||' :
					$matches = $matches || $filter->matches($class, $method, $pointcutQueryIdentifier);
				break;
				case '||!' :
					$matches = $matches || (!$filter->matches($class, $method, $pointcutQueryIdentifier));
				break;
			}
		}
		return $matches;
	}
	
	/**
	 * Adds a class filter to the composite
	 *
	 * @param  string				$operator: The operator for this filter
	 * @param  T3_FLOW3_AOP_PointcutFilterInterface		$classFilter: A configured class filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addFilter($operator, T3_FLOW3_AOP_PointcutFilterInterface $filter) {
		$this->filters[] = array($operator, $filter);
	}	
}
?>
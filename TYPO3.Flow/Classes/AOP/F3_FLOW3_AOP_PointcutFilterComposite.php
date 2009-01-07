<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * This composite allows to check for match against a row pointcut filters
 * by only one method call. All registered filters will be invoked and if one filter
 * doesn't match, the overall result is "no".
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutFilterComposite.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @see \F3\FLOW3\AOP\PointcutExpressionParser, \F3\FLOW3\AOP\PointcutClassFilter, \F3\FLOW3\AOP\PointcutMethodFilter
 */
class PointcutFilterComposite implements \F3\FLOW3\AOP\PointcutFilterInterface {

	/**
	 * @var array An array of \F3\FLOW3\AOP\Pointcut*Filter objects
	 */
	protected $filters = array();

	/**
	 * Checks if the specified class and method match the registered class-
	 * and method filter patterns.
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class The class to check against
	 * @param \F3\FLOW3\Reflection\MethodReflection $method The method to check against
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match the pattern, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		$matches = TRUE;
		foreach ($this->filters as $operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;
			switch ($operator) {
				case '&&' :
					$matches = $matches && $filter->matches($class, $method, $pointcutQueryIdentifier);
				break;
				case '&&!' :
					$matches = $matches && (!$filter->matches($class, $method, $pointcutQueryIdentifier));
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
	 * @param string$operator The operator for this filter
	 * @param \F3\FLOW3\AOP\PointcutFilterInterface $classFilter A configured class filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addFilter($operator, \F3\FLOW3\AOP\PointcutFilterInterface $filter) {
		$this->filters[] = array($operator, $filter);
	}
}
?>
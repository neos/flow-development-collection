<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Pointcut;

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
 * This composite allows to check for match against a row pointcut filters
 * by only one method call. All registered filters will be invoked and if one filter
 * doesn't match, the overall result is "no".
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see \F3\FLOW3\AOP\Pointcut\PointcutExpressionParser, \F3\FLOW3\AOP\Pointcut\PointcutClassNameFilter, \F3\FLOW3\AOP\Pointcut\PointcutMethodFilter
 * @scope prototype
 */
class PointcutFilterComposite implements \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * @var array An array of \F3\FLOW3\AOP\Pointcut\Pointcut*Filter objects
	 */
	protected $filters = array();

	/**
	 * Checks if the specified class and method match the registered class-
	 * and method filter patterns.
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method to check against
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match the pattern, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		$matches = TRUE;
		foreach ($this->filters as $operatorAndFilter) {
			list($operator, $filter) = $operatorAndFilter;
			switch ($operator) {
				case '&&' :
					$matches = $matches && $filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
				break;
				case '&&!' :
					$matches = $matches && (!$filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
				break;
				case '||' :
					$matches = $matches || $filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
				break;
				case '||!' :
					$matches = $matches || (!$filter->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier));
				break;
			}
		}
		return $matches;
	}

	/**
	 * Adds a class filter to the composite
	 *
	 * @param string $operator The operator for this filter
	 * @param \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface $filter A configured class filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addFilter($operator, \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface $filter) {
		$this->filters[] = array($operator, $filter);
	}
}
?>
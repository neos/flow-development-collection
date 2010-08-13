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
 * A simple class filter which fires on class names defined by a regular expression
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PointcutClassNameFilter implements \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * @var F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var string A regular expression to match class names
	 */
	protected $classFilterExpression;

	/**
	 * The constructor - initializes the class filter with the class filter expression
	 *
	 * @param string $classFilterExpression A regular expression which defines which class names should match
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($classFilterExpression) {
		$this->classFilterExpression = str_replace('\\', '\\\\', $classFilterExpression);
	}

	/**
	 * Injects the reflection service
	 *
	 * @param F3\FLOW3\Reflection\ReflectionService $reflectionService The reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Checks if the specified class matches with the class filter pattern
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Collect information why class was ignored for debugging in a future AOP browser
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->reflectionService->isClassFinal($className) || $this->reflectionService->isMethodFinal($className, '__construct')) return FALSE;

		$matchResult =  @preg_match('/^' . $this->classFilterExpression . '$/', $className);
		if ($matchResult === FALSE) {
			throw new \F3\FLOW3\AOP\Exception('Error in regular expression "' . $this->classFilterExpression . '" in pointcut class filter', 1168876955);
		}
		return ($matchResult === 1);
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return FALSE;
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}
}

?>
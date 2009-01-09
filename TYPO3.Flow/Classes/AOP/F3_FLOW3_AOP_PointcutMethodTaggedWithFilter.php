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
 * A method filter which fires on methods tagged with a certain annotation
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutMethodTaggedWithFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

	/**
	 * @var string A regular expression to match annotations
	 */
	protected $methodTagFilterExpression;

	/**
	 * The constructor - initializes the method tag filter with the method tag filter expression
	 *
	 * @param string $methodTagFilterExpression A regular expression which defines which method tags should match
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($methodTagFilterExpression) {
		$this->methodTagFilterExpression = $methodTagFilterExpression;
	}

	/**
	 * Checks if the specified method matches with the method tag filter pattern
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $method The class to check against
	 * @param \F3\FLOW3\Reflection\MethodReflection $method The method to check against
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the method matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		foreach ($method->getTagsValues() as $tag => $values) {
			$matchResult =  @preg_match('/^' . $this->methodTagFilterExpression . '$/', $tag);
			if ($matchResult === FALSE) {
				throw new \F3\FLOW3\AOP\Exception('Error in regular expression "' . $this->methodTagFilterExpression . '" in pointcut method tag filter', 1229343988);
			}
			if ($matchResult === 1) return TRUE;
		}
		return FALSE;
	}
}

?>
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
 * A class type filter which fires on class types defined by a regular expression
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutClassTypeFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

	/**
	 * @var string A regular expression to match class types
	 */
	protected $classTypeFilterExpression;

	/**
	 * The constructor - initializes the class type filter with the class type filter expression
	 *
	 * @param string $classTypeFilterExpression: A regular expression which defines which class types should match
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($classTypeFilterExpression) {
		$this->classTypeFilterExpression = str_replace('\\', '\\\\', $classTypeFilterExpression);
	}

	/**
	 * Checks if the specified class matches with the class type filter pattern
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class The class to check against
	 * @param \F3\FLOW3\Reflection\MethodReflection $method The method - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		$matches = FALSE;
		foreach ($class->getInterfaceNames() as $interfaceName) {
			$matchResult =  @preg_match('/^' . $this->classTypeFilterExpression . '$/', $interfaceName);
			if ($matchResult === FALSE) {
				throw new \RuntimeException('Error in regular expression "' . $this->classTypeFilterExpression . '" in pointcut class type filter', 1172483343);
			}
			if ($matchResult === 1) {
				$matches = TRUE;
			}
		}
		return ($matches);
	}
}

?>
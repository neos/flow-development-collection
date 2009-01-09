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
 * A little filter which filters for method names
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutMethodNameFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

	const PATTERN_MATCHVISIBILITYMODIFIER = '/(|public|protected|private)/';

	/**
	 * @var string The method name filter expression
	 */
	protected $methodNameFilterExpression;

	/**
	 * @var string The method visibility
	 */
	protected $methodVisibility = NULL;

	/**
	 * Constructor - initializes the filter with the name filter pattern
	 *
	 * @param string $methodNameFilterExpression A regular expression which filters method names
	 * @param string $methodVisibility The method visibility modifier (public, protected or private). Specifiy NULL if you don't care.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($methodNameFilterExpression, $methodVisibility = NULL) {
		$this->methodNameFilterExpression = $methodNameFilterExpression;
		if (preg_match(self::PATTERN_MATCHVISIBILITYMODIFIER, $methodVisibility) !== 1) throw new \RuntimeException('Invalid method visibility modifier.', 1172494794);
		$this->methodVisibility = $methodVisibility;
	}

	/**
	 * Checks if the specified method matches against the method name
	 * expression.
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class The class - won't be checked here
	 * @param \F3\FLOW3\Reflection\MethodReflection $method The method to check the name of
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the method name matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		$matchResult = preg_match('/^' . $this->methodNameFilterExpression . '$/', $method->getName());
		if ($matchResult === FALSE) {
			throw new \F3\FLOW3\AOP\Exception('Error in regular expression', 1168876915);
		}
		$methodNameMatches = ($matchResult === 1);
		switch ($this->methodVisibility) {
			case 'public' :
				$visibilityMatches = $method->isPublic();
			break;
			case 'protected' :
				$visibilityMatches = $method->isProtected();
			break;
			case 'private' :
				$visibilityMatches = $method->isPrivate();
			break;
			default :
				$visibilityMatches = TRUE;
		}
		$isNotFinal = !$method->isFinal();

		return $methodNameMatches && $visibilityMatches && $isNotFinal;
	}
}

?>
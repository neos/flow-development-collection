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
 * @version $Id$
 */

/**
 * A simple class filter which fires on class names defined by a regular expression
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilter.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutClassFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

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
	 * Checks if the specified class matches with the class filter pattern
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class The class to check against
	 * @param \F3\FLOW3\Reflection\MethodReflection $method The method - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Collect information why class was ignored for debugging in a future AOP browser
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		$constructorIsFinal = (is_object($class->getConstructor())) ? $class->getConstructor()->isFinal() : FALSE;
		if ($constructorIsFinal || $class->isFinal()) return FALSE;

		$matchResult =  @preg_match('/^' . $this->classFilterExpression . '$/', $class->getName());
		if ($matchResult === FALSE) {
			throw new \RuntimeException('Error in regular expression "' . $this->classFilterExpression . '" in pointcut class filter', 1168876955);
		}
		return ($matchResult === 1);
	}
}

?>
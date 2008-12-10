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
 * A class filter which fires on classes tagged with a certain annotation
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilter.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutClassTaggedWithFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

	/**
	 * @var string A regular expression to match annotations
	 */
	protected $classTagFilterExpression;

	/**
	 * The constructor - initializes the class tag filter with the class tag filter expression
	 *
	 * @param string $classTagFilterExpression A regular expression which defines which class tags should match
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($classTagFilterExpression) {
		$this->classTagFilterExpression = $classTagFilterExpression;
	}

	/**
	 * Checks if the specified class matches with the class tag filter pattern
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class The class to check against
	 * @param \F3\FLOW3\Reflection\ClassReflectionMethod $method The method - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		foreach ($class->getTagsValues() as $tag => $values) {
			$matchResult =  @preg_match('/^' . $this->classTagFilterExpression . '$/', $tag);
			if ($matchResult === FALSE) {
				throw new \F3\FLOW3\AOP\Exception('Error in regular expression "' . $this->classTagFilterExpression . '" in pointcut class tag filter', 1212576034);
			}
			if ($matchResult === 1) return TRUE;
		}
		return FALSE;
	}
}

?>
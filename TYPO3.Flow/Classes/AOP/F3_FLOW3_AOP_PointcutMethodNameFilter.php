<?php
declare(ENCODING = 'utf-8');

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
 * A little filter which filters for method names
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_PointcutMethodNameFilter.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_PointcutMethodNameFilter implements F3_FLOW3_AOP_PointcutFilterInterface {

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
		if (preg_match(self::PATTERN_MATCHVISIBILITYMODIFIER, $methodVisibility) !== 1)	throw new RuntimeException('Invalid method visibility modifier.', 1172494794);
		$this->methodVisibility = $methodVisibility;
	}

	/**
	 * Checks if the specified method matches against the method name
	 * expression.
	 *
	 * @param F3_FLOW3_Reflection_Class $class The class - won't be checked here
	 * @param F3_FLOW3_Reflection_Method $method The method to check the name of
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the method name matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(F3_FLOW3_Reflection_Class $class, F3_FLOW3_Reflection_Method $method, $pointcutQueryIdentifier) {
		$matchResult = preg_match('/^' . $this->methodNameFilterExpression . '$/', $method->getName());
		if ($matchResult === FALSE) {
			throw new F3_FLOW3_AOP_Exception('Error in regular expression', 1168876915);
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
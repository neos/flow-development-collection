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
 * The pointcut defines the set of join points (ie. "situations") in which certain
 * code associated with the pointcut (ie. advices) should be executed. This set of
 * join points is defined by a poincut expression which is matched against class
 * and method signatures.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_Pointcut.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_Pointcut implements F3_FLOW3_AOP_PointcutInterface {

	const MAXIMUM_RECURSIONS = 99;

	/**
	 * @var string	A pointcut expression which configures the pointcut
	 */
	protected $pointcutExpression;

	/**
	 * @var F3_FLOW3_AOP_PointcutFilterComposite: The filter composite object, created from the pointcut expression
	 */
	protected $pointcutFilterComposite;

	/**
	 * @var string If this pointcut is based on a pointcut declaration, contains the name of the aspect class where the pointcut was declared
	 */
	protected $aspectClassName;

	/**
	 * @var string If this pointcut is based on a pointcut declaration, contains the name of the method acting as the pointcut identifier
	 */
	protected $pointcutMethodName;

	/**
	 * @var mixed An identifier which is used to detect circular references between pointcuts
	 */
	protected $pointcutQueryIdentifier = NULL;

	/**
	 * @var integer Counts how often this pointcut's matches() method has been called during one query
	 */
	protected $recursionLevel = 0;

	/**
	 * The constructor
	 *
	 * @param string $pointcutExpression A pointcut expression which configures the pointcut
	 * @param F3_FLOW3_AOP_PointcutExpressionParserInterface $pointcutExpressionParser The parser to use for parsing the pointcut expression
	 * @param string $aspectClassName The name of the aspect class where the pointcut was declared (either explicitly or from an advice's pointcut expression)
	 * @param string $pointcutMethodName (optional) If the pointcut is created from a pointcut declaration, the name of the method declaring the pointcut must be passed
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($pointcutExpression, F3_FLOW3_AOP_PointcutExpressionParser $pointcutExpressionParser, $aspectClassName, $pointcutMethodName = NULL) {
		if (!is_string($pointcutExpression) || F3_PHP6_Functions::strlen($pointcutExpression) == 0) throw new F3_FLOW3_AOP_Exception_InvalidPointcutExpression('Pointcut expression must be a valid string, ' . gettype($pointcutExpression) . ' given.', 1202902188);

		$this->pointcutExpression = $pointcutExpression;
		$this->pointcutFilterComposite = $pointcutExpressionParser->parse($pointcutExpression);
		$this->aspectClassName = $aspectClassName;
		$this->pointcutMethodName = $pointcutMethodName;
	}

	/**
	 * Checks if the given class and method match this pointcut.
	 * Before each match run, reset() must be called to reset the circular references guard.
	 *
	 * @param F3_FLOW3_Reflection_Class $class Class to check against
	 * @param F3_FLOW3_Reflection_Class $method Method to check against
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match this point cut, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(F3_FLOW3_Reflection_Class $class, F3_FLOW3_Reflection_Method $method, $pointcutQueryIdentifier) {
		if ($this->pointcutQueryIdentifier === $pointcutQueryIdentifier) {
			$this->recursionLevel ++;
			if ($this->recursionLevel > self::MAXIMUM_RECURSIONS) {
				throw new RuntimeException('Circular pointcut reference detected in ' . $this->aspectClassName . '->' . $this->pointcutMethodName . ', too many recursions (Query identifier: ' . $pointcutQueryIdentifier . ').', 1172416172);
			}
		} else {
			$this->pointcutQueryIdentifier = $pointcutQueryIdentifier;
			$this->recursionLevel = 0;
		}
		return $this->pointcutFilterComposite->matches($class, $method, $pointcutQueryIdentifier);
	}

	/**
	 * Returns the pointcut expression which has been passed to the constructor.
	 * This can be used for debugging pointcuts.
	 *
	 * @return string The pointcut expression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcutExpression() {
		return $this->pointcutExpression;
	}

	/**
	 * Returns the aspect class name where the pointcut was declared.
	 *
	 * @return string The aspect class name where the pointcut was declared
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAspectClassName() {
		return $this->aspectClassName;
	}

	/**
	 * Returns the pointcut method name (if any was defined)
	 *
	 * @return string The pointcut method name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcutMethodName() {
		return $this->pointcutMethodName;
	}
}
?>